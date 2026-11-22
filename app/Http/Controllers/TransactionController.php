<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseInvitation;
use App\Models\Enrollment;
use App\Models\Transaction;
use App\Notifications\PathwiseNotification;
use App\Support\PaymentMethods;
use App\Support\PrivateFiles;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    private const PAYMENT_REVIEW_MESSAGE = 'Your payment was verified, but enrollment needs review. Contact support with your transaction number. Please do not pay again.';

    /*
    |--------------------------------------------------------------------------
    | STUDENT TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    public function studentIndex()
    {
        $transactions = Transaction::with('course')
            ->where('student_id', auth()->id())
            ->latest()
            ->get();

        return view('Student.transactions.index', compact('transactions'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PAYMENT / CHECKOUT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request, Course $course)
    {
        if (strtolower((string) $course->status) !== 'published') {
            abort(404);
        }

        if ($request->has('course_invitation_code')) {
            $value = $request->input('course_invitation_code');
            $code = is_string($value) ? CourseInvitation::normalizeCode($value) : null;

            return $code
                ? redirect()->route('course-invitations.show', $code)
                : redirect()->route('student.invitations.index')->with('error', 'Enter your invite code to continue.');
        }

        $student = $request->user();
        $checkout = DB::transaction(function () use ($course, $student): RedirectResponse|Enrollment|Transaction {
            // Reserve the payment under the same lock used by invitation redemption.
            $course = Course::lockForUpdate()->findOrFail($course->id);
            abort_unless($course->status === 'published', 404);

            $enrollment = Enrollment::where('student_id', $student->id)->where('course_id', $course->id)
                ->lockForUpdate()->first();
            if ($enrollment) {
                return $enrollment->grantsAccess()
                    ? redirect()->route('student.my-courses')->with('success', 'You are already enrolled in this course.')
                    : redirect()->route('student.course.show', $course)->with(
                        'error',
                        'Your enrollment in this course was cancelled. Please contact the course administrator to restore it.'
                    );
            }

            if (CourseInvitation::redeemedFor($student->id, $course->id)->exists()) {
                return back()->with('error', 'You previously joined this course by invitation. Contact the course administrator for access.');
            }

            $payments = Transaction::blockingCoursePayment($student->id, $course->id)
                ->latest('id')->lockForUpdate()->get();
            if ($payments->isNotEmpty()) {
                $existing = $payments->first();
                if ($payments->count() === 1 && $existing->canResumeCheckout() && (float) $course->price > 0) {
                    return redirect()->away($existing->checkout_url);
                }

                return redirect()->route('student.transactions.show', $existing)
                    ->with('error', Transaction::ENROLLMENT_PAYMENT_CONFLICT);
            }

            if ((float) $course->price <= 0) {
                return Enrollment::firstOrCreate(
                    ['student_id' => $student->id, 'course_id' => $course->id],
                    ['status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]
                );
            }

            if (! config('services.paymongo.secret_key') || ! config('services.paymongo.public_key')) {
                return back()->with('error', 'Payment service is not configured.');
            }

            return $this->createTransactionWithUniqueNumber([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'amount' => $course->price,
                'payment_method' => 'PayMongo',
                'status' => 'pending',
            ]);
        }, 3);

        if ($checkout instanceof RedirectResponse) {
            return $checkout;
        }

        if ($checkout instanceof Enrollment) {
            if (! $checkout->grantsAccess()) {
                return back()->with('error', 'Your enrollment was cancelled. Contact the course administrator for help.');
            }

            if ($checkout->wasRecentlyCreated) {
                $student->notify(new PathwiseNotification(
                    title: 'Enrollment activated',
                    message: 'You are now enrolled in "'.$course->title.'".',
                    type: 'enrollment_activated',
                    courseId: $course->id
                ));
            }

            return redirect()->route('student.my-courses')->with('success', 'You are now enrolled in this course.');
        }

        $transaction = $checkout;

        /*
        |--------------------------------------------------------------------------
        | Create PayMongo checkout session
        |--------------------------------------------------------------------------
        */

        $successUrl = route(
            'student.transactions.success',
            $transaction
        );

        $cancelUrl = route(
            'student.transactions'
        );

        try {
            $response = Http::withBasicAuth(
                config('services.paymongo.secret_key'),
                ''
            )->post(
                'https://api.paymongo.com/v1/checkout_sessions',
                [
                    'data' => [
                        'attributes' => [
                            'line_items' => [
                                [
                                    'currency' => 'PHP',
                                    'amount' => (int) round(
                                        $transaction->amount * 100
                                    ),
                                    'name' => $course->title,
                                    'quantity' => 1,
                                ],
                            ],

                            // From PAYMONGO_PAYMENT_METHODS (gcash, paymaya, card).
                            'payment_method_types' => PaymentMethods::enabled(),

                            'success_url' => $successUrl,

                            'cancel_url' => $cancelUrl,

                            'description' =>
                                'PATHWISE course enrollment: '
                                . $course->title,

                            'reference_number' =>
                                $transaction->transaction_no,

                            'metadata' => [
                                'transaction_id' => $transaction->id,
                                'course_id' => $course->id,
                                'student_id' => auth()->id(),
                            ],
                        ],
                    ],
                ]
            );
        } catch (ConnectionException $e) {

            report($e);

            $this->rejectFailedCheckout(
                $transaction,
                'PayMongo could not be reached: '
                . Str::limit($e->getMessage(), 500)
            );

            return back()->with(
                'error',
                'The payment service is not responding right now. Please try again in a few minutes.'
            );
        }

        if ($response->failed()) {

            $this->rejectFailedCheckout(
                $transaction,
                'PayMongo checkout could not be created: '
                . (
                    data_get($response->json(), 'errors.0.detail')
                    ?? 'PayMongo returned HTTP ' . $response->status()
                )
            );

            return back()->with(
                'error',
                'Unable to create the payment session. Please try again.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get PayMongo Checkout URL
        |--------------------------------------------------------------------------
        */

        $checkoutUrl = data_get(
            $response->json(),
            'data.attributes.checkout_url'
        );

        /*
        |--------------------------------------------------------------------------
        | Get PayMongo Checkout Session ID
        |--------------------------------------------------------------------------
        |
        | This is required by verifyPayMongoCheckout().
        |
        */

        $checkoutSessionId = data_get(
            $response->json(),
            'data.id'
        );

        if (! $checkoutUrl || ! $checkoutSessionId) {

            $this->rejectFailedCheckout(
                $transaction,
                'PayMongo checkout could not be created: the response had no checkout URL or session ID.',
                is_string($checkoutSessionId) ? $checkoutSessionId : null
            );

            return back()->with(
                'error',
                'PayMongo did not return a valid checkout session.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Save checkout URL + Checkout Session ID
        |--------------------------------------------------------------------------
        */

        $transaction->update([
            'checkout_url' => $checkoutUrl,
            'payment_reference' => $checkoutSessionId,
        ]);

        return redirect()->away($checkoutUrl);
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT SUCCESS / LOCAL VERIFICATION FALLBACK
    |--------------------------------------------------------------------------
    |
    | PayMongo webhooks remain the production source of truth. For local
    | development, capstone.test is not publicly reachable by PayMongo.
    | Therefore, when PayMongo redirects the student back here, PathWise
    | securely retrieves the Checkout Session using the SECRET key and checks
    | for an actual paid payment before approving the transaction.
    |
    */

    public function success(Transaction $transaction)
    {
        $this->ensureStudentOwnsTransaction($transaction);

        $transaction->load('course');

        if ($transaction->status === 'approved') {
            return view(
                'Student.transactions.success',
                [
                    'transaction' => $transaction->fresh('course'),
                    'paymentVerified' => true,
                    'courseAccessible' => $this->hasCourseAccess($transaction),
                    'verificationMessage' =>
                        'Your PayMongo payment has already been verified.',
                ]
            );
        }

        if ($transaction->payment_review_required_at) {
            return view('Student.transactions.success', [
                'transaction' => $transaction,
                'paymentVerified' => false,
                'paymentNeedsReview' => true,
                'verificationMessage' => self::PAYMENT_REVIEW_MESSAGE,
            ]);
        }

        $verification = [
            'paid' => false,
            'payment_id' => null,
            'message' => 'Payment has not been confirmed yet.',
        ];

        /*
        |--------------------------------------------------------------------------
        | PayMongo may redirect the browser a fraction of a second before the
        | payment record is available through retrieval. Retry briefly.
        |--------------------------------------------------------------------------
        */

        for ($attempt = 1; $attempt <= 3; $attempt++) {

            $verification = $this->verifyPayMongoCheckout(
                $transaction
            );

            if ($verification['paid']) {
                break;
            }

            if ($attempt < 3) {
                usleep(600000);
            }
        }

        $reviewMessage = null;
        if ($verification['paid']) {
            $reviewMessage = $this->approveVerifiedPayMongoTransaction(
                $transaction,
                $verification['payment_id'],
                $verification['method'] ?? null
            );

            $transaction = $transaction->fresh('course');
        }

        return view(
            'Student.transactions.success',
            [
                'transaction' => $transaction,
                'paymentVerified' =>
                    $transaction->status === 'approved',
                'courseAccessible' => $this->hasCourseAccess($transaction),
                'paymentNeedsReview' => $reviewMessage !== null,
                'verificationMessage' => $reviewMessage ?? $verification['message'],
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CANCEL PAGE
    |--------------------------------------------------------------------------
    */

    public function cancel(Transaction $transaction)
    {
        $this->ensureStudentOwnsTransaction($transaction);

        // Closing a page cannot confirm cancellation at PayMongo. An in-flight
        // reservation stays pending, and a saved session stays on the record.
        Transaction::whereKey($transaction->id)
            ->where('status', 'pending')
            ->whereNull('payment_review_required_at')
            ->whereNotNull('payment_reference')
            ->update([
                'status' => 'rejected',
                'remarks' => 'PayMongo checkout was cancelled by the student.',
            ]);

        $transaction->refresh();
        $transaction->load('course');

        return view(
            'Student.transactions.cancel',
            compact('transaction')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STUDENT TRANSACTION DETAILS
    |--------------------------------------------------------------------------
    */

    public function studentShow(Transaction $transaction)
    {
        $this->ensureStudentOwnsTransaction($transaction);

        /*
        |--------------------------------------------------------------------------
        | PayMongo transactions never require a manually uploaded receipt.
        |--------------------------------------------------------------------------
        */

        if (
            strcasecmp(
                (string) $transaction->payment_method,
                'PayMongo'
            ) === 0
        ) {
            return redirect()->route(
                'student.transactions.success',
                $transaction
            );
        }

        $transaction->load('course');

        return view(
            'Student.transactions.upload-proof',
            compact('transaction')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY MANUAL PAYMENT PROOF
    |--------------------------------------------------------------------------
    */

    public function uploadProof(
        Request $request,
        Transaction $transaction
    ) {
        $this->ensureStudentOwnsTransaction($transaction);

        if (
            strcasecmp(
                (string) $transaction->payment_method,
                'PayMongo'
            ) === 0
        ) {
            return redirect()
                ->route(
                    'student.transactions.show',
                    $transaction
                )
                ->with(
                    'error',
                    'PayMongo payments are verified automatically. No payment proof upload is required.'
                );
        }

        $validated = $request->validate([
            // The enabled PayMongo methods' labels, or bank transfer.
            'payment_method' => [
                'required',
                'string',
                Rule::in(PaymentMethods::proofOptions()),
            ],

            'payment_reference' =>
                'nullable|string|max:255',

            'payment_proof' =>
                'required|image|mimes:jpg,jpeg,png|max:5120',

            'remarks' =>
                'nullable|string|max:1000',
        ]);

        $proofPath = $request
            ->file('payment_proof')
            ->store(
                'payment-proofs',
                PrivateFiles::DISK
            );

        $transaction->update([
            'payment_method' =>
                $validated['payment_method'],

            'payment_reference' =>
                $validated['payment_reference']
                ?? null,

            'payment_proof' =>
                $proofPath,

            'status' =>
                'pending',

            'remarks' =>
                $validated['remarks']
                ?? null,
        ]);

        return redirect()
            ->route(
                'student.transactions.show',
                $transaction
            )
            ->with(
                'success',
                'Payment proof submitted successfully and is awaiting verification.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMONGO WEBHOOK
    |--------------------------------------------------------------------------
    */

    public function webhook(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Verify PayMongo's signature
        |--------------------------------------------------------------------------
        */

        $signatureVerification =
            $this->verifyPayMongoWebhookSignature(
                $request
            );

        if (! $signatureVerification['valid']) {

            return response()->json(
                [
                    'message' =>
                        $signatureVerification['message'],
                ],
                $signatureVerification['status']
            );
        }

        $rawBody = $request->getContent();

        $event = json_decode(
            $rawBody,
            true
        );

        if (! is_array($event)) {

            return response()->json(
                [
                    'message' =>
                        'Invalid webhook payload.',
                ],
                400
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Support Hosted Checkout and older/general PayMongo event envelopes.
        |--------------------------------------------------------------------------
        */

        $eventTypeCandidates = [
            data_get(
                $event,
                'data.type'
            ),

            data_get(
                $event,
                'data.attributes.type'
            ),
        ];

        $eventType =
            collect($eventTypeCandidates)
                ->filter()
                ->first(
                    fn ($type) =>
                        $type ===
                        'checkout_session.payment.paid'
                )
            ??
            collect($eventTypeCandidates)
                ->filter()
                ->first();

        if (
            $eventType !==
            'checkout_session.payment.paid'
        ) {
            return response()->json(
                [
                    'message' =>
                        'Event ignored.',
                ],
                200
            );
        }

        $session =
            data_get(
                $event,
                'data.data'
            )
            ??
            data_get(
                $event,
                'data.attributes.data'
            );

        if (! is_array($session)) {

            return response()->json(
                [
                    'message' =>
                        'Checkout session data missing.',
                ],
                400
            );
        }

        $transactionNo = data_get(
            $session,
            'attributes.reference_number'
        );

        $checkoutSessionId = data_get(
            $session,
            'id'
        );

        if (! $transactionNo) {

            return response()->json(
                [
                    'message' =>
                        'Reference number missing.',
                ],
                400
            );
        }

        $transaction = Transaction::where(
            'transaction_no',
            $transactionNo
        )->first();

        if (! $transaction) {

            return response()->json(
                [
                    'message' =>
                        'Transaction not found.',
                ],
                404
            );
        }

        if (
            $checkoutSessionId
            && $transaction->payment_reference
            && ! hash_equals(
                (string) $transaction->payment_reference,
                (string) $checkoutSessionId
            )
        ) {
            return response()->json(
                [
                    'message' =>
                        'Checkout session mismatch.',
                ],
                400
            );
        }

        if ($transaction->status === 'approved') {
            return response()->json(
                [
                    'message' =>
                        'Transaction already processed.',
                ],
                200
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Signature verification proves origin.
        | Re-query exact Checkout Session for second verification.
        |--------------------------------------------------------------------------
        */

        if ($transaction->payment_review_required_at) {
            return response()->json(['message' => 'Verified payment requires enrollment review.']);
        }

        $verification =
            $this->verifyPayMongoCheckout(
                $transaction
            );

        if (! $verification['paid']) {

            return response()->json(
                [
                    'message' =>
                        $verification['message'],
                ],
                202
            );
        }

        $reviewMessage = $this->approveVerifiedPayMongoTransaction(
            $transaction,
            $verification['payment_id'],
            $verification['method'] ?? null
        );

        return response()->json([
            'message' => $reviewMessage ? 'Verified payment requires enrollment review.' : 'Payment successfully processed.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN TRANSACTIONS
    |--------------------------------------------------------------------------
    */

    public function adminIndex()
    {
        $transactions = Transaction::with([
            'student',
            'course',
        ])
            ->latest()
            ->get();

        return view(
            'admin.transactions.index',
            compact('transactions')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN APPROVE
    |--------------------------------------------------------------------------
    */

    public function approve(Transaction $transaction)
    {
        /*
        |--------------------------------------------------------------------------
        | Already finalized
        |--------------------------------------------------------------------------
        */

        if ($transaction->status === 'approved') {

            return back()->with(
                'success',
                'This transaction has already been approved.'
            );
        }

        if ($transaction->payment_review_required_at) {
            return back()->with('error', self::PAYMENT_REVIEW_MESSAGE);
        }

        if ($transaction->status !== 'pending') {

            return back()->with(
                'error',
                'Only pending transactions can be approved.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PayMongo transactions
        |--------------------------------------------------------------------------
        */

        if (
            strcasecmp(
                (string) $transaction->payment_method,
                'PayMongo'
            ) === 0
        ) {

            $verification =
                $this->verifyPayMongoCheckout(
                    $transaction
                );

            if (! $verification['paid']) {

                return back()->with(
                    'error',
                    $verification['message']
                );
            }

            $reviewMessage = $this->approveVerifiedPayMongoTransaction(
                $transaction,
                $verification['payment_id'],
                $verification['method'] ?? null
            );

            if ($reviewMessage) {
                return back()->with('error', $reviewMessage);
            }

            return back()->with(
                'success',
                'PayMongo payment verified and transaction approved successfully.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy/manual transactions
        |--------------------------------------------------------------------------
        */

        if (empty($transaction->payment_proof)) {

            return back()->with(
                'error',
                'A payment proof is required before this manual transaction can be approved.'
            );
        }

        $approved = DB::transaction(function () use ($transaction) {
            $course = Course::lockForUpdate()->findOrFail($transaction->course_id);
            $current = Transaction::lockForUpdate()->findOrFail($transaction->id);
            if ($current->status !== 'pending' || $current->payment_review_required_at
                || ! $this->canGrantEnrollmentForPayment($course, $current)
                || ! $this->activateEnrollment($current->student_id, $current->course_id)) {
                return false;
            }

            $current->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return true;
        }, 3);

        if (! $approved) {
            return back()->with('error', 'Enrollment was not granted. Review the transaction, invitation, and enrollment status.');
        }

        $this->notifyStudentAboutApprovedTransaction(
            $transaction->fresh()
        );

        return back()->with(
            'success',
            'Transaction approved and student enrolled successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN REJECT
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        Transaction $transaction
    ) {
        if ($transaction->status === 'approved') {

            return back()->with(
                'error',
                'An approved transaction cannot be rejected.'
            );
        }

        if ($transaction->status !== 'pending') {

            return back()->with(
                'error',
                'Only pending transactions can be rejected.'
            );
        }

        $request->validate([
            'remarks' =>
                'nullable|string|max:1000',
        ]);

        $rejected = Transaction::whereKey($transaction->id)
            ->where('status', 'pending')
            ->whereNull('payment_review_required_at')
            ->where(fn ($payments) => $payments->whereRaw('LOWER(payment_method) <> ?', ['paymongo'])
                ->orWhereNotNull('payment_reference'))
            ->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'remarks' => $request->input('remarks', 'Payment rejected by administrator.'),
            ]);

        if (! $rejected) {
            return back()->with('error', 'This transaction is being processed or requires payment reconciliation.');
        }

        return back()->with(
            'success',
            'Transaction rejected successfully.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMONGO VERIFICATION HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, mixed>
     */
    private function verifyPayMongoCheckout(
        Transaction $transaction
    ): array {
        $secretKey = $this->payMongoSecretKey();

        $checkoutSessionId = trim(
            (string) $transaction->payment_reference
        );

        if ($secretKey === '') {

            return [
                'paid' => false,
                'payment_id' => null,
                'message' =>
                    'PayMongo secret key is not configured.',
            ];
        }

        if (
            $checkoutSessionId === ''
            || ! Str::startsWith(
                $checkoutSessionId,
                'cs_'
            )
        ) {
            return [
                'paid' => false,
                'payment_id' => null,
                'message' =>
                    'This transaction does not have a valid PayMongo Checkout Session reference.',
            ];
        }

        try {

            $response = Http::withBasicAuth(
                $secretKey,
                ''
            )
                ->acceptJson()
                ->timeout(30)
                ->get(
                    'https://api.paymongo.com/v1/checkout_sessions/'
                    . rawurlencode(
                        $checkoutSessionId
                    )
                );

            if ($response->failed()) {

                $detail = data_get(
                    $response->json(),
                    'errors.0.detail'
                )
                    ??
                    (
                        'PayMongo returned HTTP '
                        . $response->status()
                    );

                return [
                    'paid' => false,
                    'payment_id' => null,
                    'message' =>
                        'PathWise could not verify the PayMongo checkout yet: '
                        . $detail,
                ];
            }

            $session = $response->json(
                'data'
            );

            $returnedSessionId =
                (string) data_get(
                    $session,
                    'id',
                    ''
                );

            $referenceNumber =
                (string) data_get(
                    $session,
                    'attributes.reference_number',
                    ''
                );

            if (
                ! hash_equals(
                    $checkoutSessionId,
                    $returnedSessionId
                )
                ||
                ! hash_equals(
                    (string) $transaction->transaction_no,
                    $referenceNumber
                )
            ) {

                return [
                    'paid' => false,
                    'payment_id' => null,
                    'message' =>
                        'PayMongo returned a checkout session that does not match this PathWise transaction.',
                ];
            }

            $payments = data_get(
                $session,
                'attributes.payments',
                []
            );

            if (! is_array($payments)) {
                $payments = [];
            }

            $expectedAmount = (int) round(
                ((float) $transaction->amount) * 100
            );

            foreach ($payments as $payment) {

                $status = strtolower(
                    (string) data_get(
                        $payment,
                        'attributes.status',
                        ''
                    )
                );

                $currency = strtoupper(
                    (string) data_get(
                        $payment,
                        'attributes.currency',
                        ''
                    )
                );

                $amount = (int) data_get(
                    $payment,
                    'attributes.amount',
                    -1
                );

                if (
                    $status === 'paid'
                    && $currency === 'PHP'
                    && $amount === $expectedAmount
                ) {

                    return [
                        'paid' => true,
                        'payment_id' =>
                            data_get(
                                $payment,
                                'id'
                            ),
                        // The method the student used (gcash, paymaya or card).
                        'method' =>
                            PaymentMethods::normalize(
                                data_get($payment, 'attributes.source.type')
                                ?? data_get($session, 'attributes.payment_method_used')
                            ),
                        'message' =>
                            'PayMongo confirmed the payment successfully.',
                    ];
                }
            }

            return [
                'paid' => false,
                'payment_id' => null,
                'message' =>
                    'PayMongo has not returned a completed paid payment for this transaction yet.',
            ];

        } catch (\Throwable $e) {

            report($e);

            return [
                'paid' => false,
                'payment_id' => null,
                'message' =>
                    'PathWise could not contact PayMongo to verify the payment. Please try again.',
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | APPROVE VERIFIED PAYMONGO TRANSACTION
    |--------------------------------------------------------------------------
    */

    private function approveVerifiedPayMongoTransaction(
        Transaction $transaction,
        ?string $paymentId = null,
        ?string $paymongoMethod = null
    ): ?string {
        [$approved, $reviewMessage] = DB::transaction(function () use ($transaction, $paymentId, $paymongoMethod) {
            // Keep the order consistent with checkout and invitation writes.
            $course = Course::lockForUpdate()->findOrFail($transaction->course_id);
            $current = Transaction::lockForUpdate()->findOrFail($transaction->id);
            if ($current->status === 'approved') {
                return [false, null];
            }

            $paymentDetails = $paymentId ? ' Payment ID: '.$paymentId : '';
            if ($current->payment_review_required_at) {
                return [false, self::PAYMENT_REVIEW_MESSAGE];
            }

            if (! $this->canGrantEnrollmentForPayment($course, $current)
                || ! $this->activateEnrollment($current->student_id, $current->course_id)) {
                $current->forceFill([
                    'payment_review_required_at' => now(),
                    'payment_method' => 'PayMongo',
                    'paymongo_method' => $paymongoMethod,
                    'remarks' => Str::limit('Verified payment requires enrollment review.'.$paymentDetails, 1000),
                ])->save();

                return [false, self::PAYMENT_REVIEW_MESSAGE];
            }

            $current->update([
                'status' => 'approved',
                'payment_method' => 'PayMongo',
                'paymongo_method' => $paymongoMethod,
                'remarks' => Str::limit('Payment securely verified through PayMongo.'.$paymentDetails, 1000),
                'approved_at' => now(),
            ]);

            return [true, null];
        }, 3);

        if ($approved) {
            $this->notifyStudentAboutApprovedTransaction($transaction->fresh());
        }

        return $reviewMessage;
    }

    private function canGrantEnrollmentForPayment(Course $course, Transaction $transaction): bool
    {
        $student = $transaction->student;
        if ($course->status !== 'published' || ! $student || $student->is_active === false
            || ! $student->hasRole('student') || ! $student->hasVerifiedEmail() || $student->awaitingStudentApproval()) {
            return false;
        }

        if ($transaction->course_invitation_id) {
            $invitation = CourseInvitation::findForLockedCourse($course, $transaction->course_invitation_id);
            if (! $invitation || ! $invitation->canBeOpenedBy($student->id) || ! $invitation->isValid()) {
                return false;
            }
        }

        return ! CourseInvitation::redeemedFor($student->id, $course->id)->exists()
            && ! Transaction::forStudentCourse($student->id, $course->id)->where('id', '<>', $transaction->id)
                ->where(fn ($payments) => $payments->where('status', 'approved')->orWhereNotNull('payment_review_required_at'))
                ->lockForUpdate()->first();
    }

    private function hasCourseAccess(Transaction $transaction): bool
    {
        return $transaction->course?->status === 'published'
            && Enrollment::where('student_id', $transaction->student_id)
                ->where('course_id', $transaction->course_id)
                ->withAccess()
                ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT / ENROLLMENT NOTIFICATION
    |--------------------------------------------------------------------------
    |
    | Notification failure must never undo a verified payment or enrollment.
    |
    */

    private function notifyStudentAboutApprovedTransaction(
        Transaction $transaction
    ): void {
        try {

            $transaction->loadMissing([
                'student',
                'course',
            ]);

            $student =
                $transaction->student;

            $course =
                $transaction->course;

            if (
                ! $student
                || ! $course
                || $transaction->status !== 'approved'
            ) {
                return;
            }

            $student->notify(
                new PathwiseNotification(
                    title: 'Payment confirmed',
                    message:
                        'Your payment for "'
                        . $course->title
                        . '" has been confirmed and your enrollment is active.',
                    type: 'payment_approved',
                    courseId: $course->id,
                    transactionId: $transaction->id
                )
            );

        } catch (\Throwable $e) {

            report($e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Mark a transaction whose PayMongo checkout could not be created.
    |--------------------------------------------------------------------------
    |
    | The status column only allows pending, approved and rejected, so a
    | failed checkout is stored as rejected with the reason in remarks.
    |
    */

    private function rejectFailedCheckout(Transaction $transaction, string $reason, ?string $sessionId = null): void
    {
        Transaction::whereKey($transaction->id)->where('status', 'pending')->whereNull('payment_review_required_at')
            ->update([
                'status' => 'rejected',
                'remarks' => $reason,
                'payment_reference' => $sessionId,
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVATE ENROLLMENT
    |--------------------------------------------------------------------------
    */

    private function activateEnrollment(int $studentId, int $courseId): bool
    {
        $enrollment = Enrollment::where('student_id', $studentId)->where('course_id', $courseId)
            ->lockForUpdate()->first() ?? Enrollment::firstOrCreate(
                ['student_id' => $studentId, 'course_id' => $courseId],
                ['status' => 'active', 'enrolled_at' => now(), 'progress_percentage' => 0]
            );

        return $enrollment->grantsAccess();
    }

    /*
    |--------------------------------------------------------------------------
    | AUTHORIZATION
    |--------------------------------------------------------------------------
    */

    private function ensureStudentOwnsTransaction(
        Transaction $transaction
    ): void {
        if (
            (int) $transaction->student_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Unauthorized'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMONGO WEBHOOK SIGNATURE
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, mixed>
     */
    private function verifyPayMongoWebhookSignature(
        Request $request
    ): array {
        $webhookSecret =
            $this->payMongoWebhookSecret();

        if ($webhookSecret === '') {

            return [
                'valid' => false,
                'status' => 503,
                'message' =>
                    'PayMongo webhook secret is not configured.',
            ];
        }

        $signatureHeader = trim(
            (string) (
                $request->header(
                    'Paymongo-Signature'
                )
                ??
                $request->header(
                    'X-Paymongo-Signature'
                )
                ??
                ''
            )
        );

        if ($signatureHeader === '') {

            return [
                'valid' => false,
                'status' => 401,
                'message' =>
                    'Missing PayMongo webhook signature.',
            ];
        }

        $signatureParts = [];

        foreach (
            explode(
                ',',
                $signatureHeader
            ) as $part
        ) {

            $pair = explode(
                '=',
                trim($part),
                2
            );

            if (count($pair) !== 2) {
                continue;
            }

            $key =
                trim($pair[0]);

            $value =
                trim($pair[1]);

            if (
                in_array(
                    $key,
                    [
                        't',
                        'te',
                        'li',
                    ],
                    true
                )
            ) {
                $signatureParts[$key] =
                    $value;
            }
        }

        $timestamp =
            $signatureParts['t']
            ??
            '';

        if (
            $timestamp === ''
            ||
            ! ctype_digit(
                $timestamp
            )
        ) {

            return [
                'valid' => false,
                'status' => 401,
                'message' =>
                    'Invalid PayMongo webhook timestamp.',
            ];
        }

        $tolerance =
            $this->payMongoWebhookTolerance();

        if (
            $tolerance > 0
            &&
            abs(
                now()->getTimestamp()
                -
                (int) $timestamp
            )
            >
            $tolerance
        ) {

            return [
                'valid' => false,
                'status' => 401,
                'message' =>
                    'PayMongo webhook timestamp is outside the allowed tolerance.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PayMongo uses "te" for test mode and "li" for live mode.
        |--------------------------------------------------------------------------
        */

        $signatureKey =
            Str::startsWith(
                $this->payMongoSecretKey(),
                'sk_live_'
            )
                ? 'li'
                : 'te';

        $providedSignature =
            $signatureParts[$signatureKey]
            ??
            '';

        if ($providedSignature === '') {

            return [
                'valid' => false,
                'status' => 401,
                'message' =>
                    'PayMongo webhook signature does not match the configured environment.',
            ];
        }

        $signedPayload =
            $timestamp
            .
            '.'
            .
            $request->getContent();

        $expectedSignature =
            hash_hmac(
                'sha256',
                $signedPayload,
                $webhookSecret
            );

        if (
            ! hash_equals(
                $expectedSignature,
                $providedSignature
            )
        ) {

            return [
                'valid' => false,
                'status' => 401,
                'message' =>
                    'Invalid PayMongo webhook signature.',
            ];
        }

        return [
            'valid' => true,
            'status' => 200,
            'message' =>
                'PayMongo webhook signature verified.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMONGO CONFIG
    |--------------------------------------------------------------------------
    */

    private function payMongoWebhookSecret(): string
    {
        return trim(
            (string) config(
                'services.paymongo.webhook_secret'
            )
        );
    }

    private function payMongoWebhookTolerance(): int
    {
        return max(
            0,
            (int) config(
                'services.paymongo.webhook_tolerance',
                300
            )
        );
    }

    private function payMongoSecretKey(): string
    {
        return trim(
            (string) config(
                'services.paymongo.secret_key'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSACTION NUMBER
    |--------------------------------------------------------------------------
    */

    /*
    | Format (unchanged): TRX-YYYYMMDD-00001, numbered per day.
    |
    | System finalization L-1: the next number used to be "today's count + 1",
    | so two purchases at the same moment got the same number and one failed
    | on the unique index. The number now continues from today's highest
    | number, and if another purchase takes that number first, the unique
    | index rejects the insert and a fresh number is tried.
    */
    private const TRANSACTION_NUMBER_ATTEMPTS = 5;

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createTransactionWithUniqueNumber(array $attributes): Transaction
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return Transaction::create($attributes + [
                    'transaction_no' => $this->generateTransactionNumber(),
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt >= self::TRANSACTION_NUMBER_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    private function generateTransactionNumber(): string
    {
        $prefix = 'TRX-'.now()->format('Ymd').'-';

        // Longest first, then highest: correct even past 99999 in one day.
        $latest = Transaction::where('transaction_no', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(transaction_no) DESC')
            ->orderByDesc('transaction_no')
            ->value('transaction_no');

        $next = $latest === null
            ? 1
            : ((int) substr((string) $latest, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
