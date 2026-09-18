<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Transaction;
use App\Notifications\PathwiseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
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

        return view('student.transactions.index', compact('transactions'));
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE PAYMENT / CHECKOUT
    |--------------------------------------------------------------------------
    */

    public function store(Course $course)
    {
        if (strtolower((string) $course->status) !== 'published') {
            abort(404, 'This course is not available for purchase.');
        }

        /*
        |--------------------------------------------------------------------------
        | FREE COURSE
        |--------------------------------------------------------------------------
        */

        if ((float) $course->price <= 0) {
            $this->activateEnrollment(
                auth()->id(),
                $course->id
            );

            $student = auth()->user();

            if ($student) {
                try {
                    $student->notify(
                        new PathwiseNotification(
                            title: 'Enrollment confirmed',
                            message:
                                'You are now enrolled in "'
                                . $course->title
                                . '".',
                            type: 'enrollment_activated',
                            courseId: $course->id
                        )
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return redirect()
                ->route('student.my-courses')
                ->with('success', 'You are now enrolled in this free course.');
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING ENROLLMENT
        |--------------------------------------------------------------------------
        */

        $existingEnrollment = Enrollment::where('student_id', auth()->id())
            ->where('course_id', $course->id)
            ->first();

        if ($existingEnrollment) {
            return redirect()
                ->route('student.my-courses')
                ->with('success', 'You are already enrolled in this course.');
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK PAYMONGO CONFIGURATION BEFORE CREATING A TRANSACTION
        |--------------------------------------------------------------------------
        */

        $secretKey = $this->payMongoSecretKey();

        if ($secretKey === '') {
            return back()->with(
                'error',
                'PayMongo is not configured yet. Add PAYMONGO_SECRET_KEY to your .env file, then clear the Laravel config cache.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING PENDING TRANSACTION
        |--------------------------------------------------------------------------
        */

        $existingPendingTransaction = Transaction::where('student_id', auth()->id())
            ->where('course_id', $course->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($existingPendingTransaction) {
            return redirect()
                ->route(
                    'student.transactions.show',
                    $existingPendingTransaction
                )
                ->with(
                    'success',
                    'You already have a pending PayMongo transaction for this course. PathWise will check its payment status.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE PATHWISE TRANSACTION
        |--------------------------------------------------------------------------
        */

        $transaction = Transaction::create([
            'student_id' => auth()->id(),
            'course_id' => $course->id,
            'transaction_no' => $this->generateTransactionNumber(),
            'amount' => $course->price,
            'payment_method' => 'PayMongo',
            'status' => 'pending',
        ]);

        /*
        |--------------------------------------------------------------------------
        | CREATE PAYMONGO CHECKOUT SESSION
        |--------------------------------------------------------------------------
        */

        try {
            $response = Http::withBasicAuth(
                $secretKey,
                ''
            )
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post(
                    'https://api.paymongo.com/v2/checkout_sessions',
                    [
                        'data' => [
                            'attributes' => [
                                'line_items' => [
                                    [
                                        'name' => $course->title,
                                        'amount' => (int) round(((float) $course->price) * 100),
                                        'currency' => 'PHP',
                                        'quantity' => 1,
                                    ],
                                ],

                                'payment_method_types' => [
                                    'gcash',
                                    'card',
                                ],

                                'success_url' => route(
                                    'student.transactions.success',
                                    $transaction
                                ),

                                'cancel_url' => route(
                                    'student.transactions.cancel',
                                    $transaction
                                ),

                                'reference_number' => $transaction->transaction_no,

                                'send_email_receipt' => false,

                                'metadata' => [
                                    'transaction_id' => (string) $transaction->id,
                                    'transaction_no' => $transaction->transaction_no,
                                    'student_id' => (string) auth()->id(),
                                    'course_id' => (string) $course->id,
                                ],
                            ],
                        ],
                    ]
                );

            if ($response->failed()) {
                $paymongoDetail = data_get(
                    $response->json(),
                    'errors.0.detail'
                )
                    ?? data_get(
                        $response->json(),
                        'errors.0.code'
                    )
                    ?? (
                        'PayMongo returned HTTP '
                        . $response->status()
                    );

                $transaction->update([
                    'status' => 'rejected',
                    'remarks' => Str::limit(
                        'PayMongo checkout error: ' . $paymongoDetail,
                        1000
                    ),
                ]);

                return back()->with(
                    'error',
                    'PayMongo could not create the checkout: ' . $paymongoDetail
                );
            }

            $checkoutSession = $response->json('data');

            $checkoutUrl = data_get(
                $checkoutSession,
                'attributes.checkout_url'
            );

            $checkoutSessionId = data_get(
                $checkoutSession,
                'id'
            );

            if (!$checkoutSessionId || !Str::startsWith($checkoutSessionId, 'cs_')) {
                $transaction->update([
                    'status' => 'rejected',
                    'remarks' => 'PayMongo did not return a valid checkout session ID.',
                ]);

                return back()->with(
                    'error',
                    'PayMongo did not return a valid checkout session.'
                );
            }

            $transaction->update([
                'payment_reference' => $checkoutSessionId,
                'payment_method' => 'PayMongo',
            ]);

            if (!$checkoutUrl) {
                $transaction->update([
                    'status' => 'rejected',
                    'remarks' => 'PayMongo checkout session did not return a checkout URL.',
                ]);

                return back()->with(
                    'error',
                    'PayMongo created a response but did not return a checkout URL.'
                );
            }

            return redirect()->away($checkoutUrl);
        } catch (\Throwable $e) {
            $transaction->update([
                'status' => 'rejected',
                'remarks' => Str::limit(
                    'PayMongo connection error: ' . $e->getMessage(),
                    1000
                ),
            ]);

            report($e);

            return back()->with(
                'error',
                'A PayMongo connection error occurred: ' . $e->getMessage()
            );
        }
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
            $this->activateEnrollment(
                $transaction->student_id,
                $transaction->course_id
            );

            return view(
                'student.transactions.success',
                [
                    'transaction' => $transaction->fresh('course'),
                    'paymentVerified' => true,
                    'verificationMessage' => 'Your PayMongo payment has already been verified.',
                ]
            );
        }

        $verification = [
            'paid' => false,
            'payment_id' => null,
            'message' => 'Payment has not been confirmed yet.',
        ];

        /*
         * PayMongo may redirect the browser a fraction of a second before the
         * payment record is available through retrieval. Retry briefly so the
         * local development flow remains smooth without trusting the redirect.
         */
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $verification = $this->verifyPayMongoCheckout($transaction);

            if ($verification['paid']) {
                break;
            }

            if ($attempt < 3) {
                usleep(600000);
            }
        }

        if ($verification['paid']) {
            $this->approveVerifiedPayMongoTransaction(
                $transaction,
                $verification['payment_id']
            );

            $transaction = $transaction->fresh('course');
        }

        return view(
            'student.transactions.success',
            [
                'transaction' => $transaction,
                'paymentVerified' => $transaction->status === 'approved',
                'verificationMessage' => $verification['message'],
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

        if ($transaction->status === 'pending') {
            $transaction->update([
                'status' => 'rejected',
                'remarks' => 'PayMongo checkout was cancelled by the student.',
            ]);
        }

        $transaction->load('course');

        return view(
            'student.transactions.cancel',
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
         * PayMongo transactions never require a manually uploaded receipt.
         * View Transaction instead performs a secure PayMongo status check.
         */
        if (strcasecmp((string) $transaction->payment_method, 'PayMongo') === 0) {
            return redirect()->route(
                'student.transactions.success',
                $transaction
            );
        }

        $transaction->load('course');

        return view(
            'student.transactions.upload-proof',
            compact('transaction')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY MANUAL PAYMENT PROOF
    |--------------------------------------------------------------------------
    |
    | Kept only so older non-PayMongo transactions do not crash. New paid
    | course purchases should use PayMongo and never come through this method.
    |
    */

    public function uploadProof(
        Request $request,
        Transaction $transaction
    ) {
        $this->ensureStudentOwnsTransaction($transaction);

        if (strcasecmp((string) $transaction->payment_method, 'PayMongo') === 0) {
            return redirect()
                ->route('student.transactions.show', $transaction)
                ->with(
                    'error',
                    'PayMongo payments are verified automatically. No payment proof upload is required.'
                );
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $proofPath = $request
            ->file('payment_proof')
            ->store('payment-proofs', 'public');

        $transaction->update([
            'payment_method' => $validated['payment_method'],
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_proof' => $proofPath,
            'status' => 'pending',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('student.transactions.show', $transaction)
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
         * Verify PayMongo's signature before parsing or processing the
         * webhook body.
         */
        $signatureVerification =
            $this->verifyPayMongoWebhookSignature(
                $request
            );

        if (!$signatureVerification['valid']) {
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

        if (!is_array($event)) {
            return response()->json([
                'message' => 'Invalid webhook payload.',
            ], 400);
        }

        /*
         * Support Hosted Checkout and older/general PayMongo event envelopes.
         */
        $eventTypeCandidates = [
            data_get($event, 'data.type'),
            data_get($event, 'data.attributes.type'),
        ];

        $eventType =
            collect($eventTypeCandidates)
                ->filter()
                ->first(
                    fn ($type) =>
                        $type === 'checkout_session.payment.paid'
                )
            ??
            collect($eventTypeCandidates)
                ->filter()
                ->first();

        if ($eventType !== 'checkout_session.payment.paid') {
            return response()->json([
                'message' => 'Event ignored.',
            ], 200);
        }

        $session =
            data_get($event, 'data.data')
            ??
            data_get($event, 'data.attributes.data');

        if (!is_array($session)) {
            return response()->json([
                'message' => 'Checkout session data missing.',
            ], 400);
        }

        $transactionNo = data_get(
            $session,
            'attributes.reference_number'
        );

        $checkoutSessionId = data_get(
            $session,
            'id'
        );

        if (!$transactionNo) {
            return response()->json([
                'message' => 'Reference number missing.',
            ], 400);
        }

        $transaction = Transaction::where(
            'transaction_no',
            $transactionNo
        )->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found.',
            ], 404);
        }

        if (
            $checkoutSessionId
            && $transaction->payment_reference
            && !hash_equals(
                (string) $transaction->payment_reference,
                (string) $checkoutSessionId
            )
        ) {
            return response()->json([
                'message' => 'Checkout session mismatch.',
            ], 400);
        }

        if ($transaction->status === 'approved') {
            $this->activateEnrollment(
                $transaction->student_id,
                $transaction->course_id
            );

            return response()->json([
                'message' => 'Transaction already processed.',
            ], 200);
        }

        /*
         * Signature verification proves origin. Re-querying the exact
         * Checkout Session provides a second verification layer.
         */
        $verification =
            $this->verifyPayMongoCheckout(
                $transaction
            );

        if (!$verification['paid']) {
            return response()->json([
                'message' => $verification['message'],
            ], 202);
        }

        $this->approveVerifiedPayMongoTransaction(
            $transaction,
            $verification['payment_id']
        );

        return response()->json([
            'message' => 'Payment successfully processed.',
        ], 200);
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
         * Do not allow an already finalized transaction to be changed through
         * the manual approval action.
         */
        if ($transaction->status === 'approved') {
            return back()->with(
                'success',
                'This transaction has already been approved.'
            );
        }

        if ($transaction->status !== 'pending') {
            return back()->with(
                'error',
                'Only pending transactions can be approved.'
            );
        }

        /*
         * PayMongo transactions must never be approved from the dashboard
         * based only on a button click. Re-query PayMongo first and approve
         * only when the stored Checkout Session contains a real paid payment.
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

            if (!$verification['paid']) {
                return back()->with(
                    'error',
                    $verification['message']
                );
            }

            $this->approveVerifiedPayMongoTransaction(
                $transaction,
                $verification['payment_id']
            );

            return back()->with(
                'success',
                'PayMongo payment verified and transaction approved successfully.'
            );
        }

        /*
         * Legacy/manual transactions still require an uploaded proof before
         * an administrator can approve them.
         */
        if (empty($transaction->payment_proof)) {
            return back()->with(
                'error',
                'A payment proof is required before this manual transaction can be approved.'
            );
        }

        DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' =>
                    'approved',

                'approved_by' =>
                    auth()->id(),

                'approved_at' =>
                    now(),
            ]);

            $this->activateEnrollment(
                $transaction->student_id,
                $transaction->course_id
            );
        });

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

        $transaction->update([
            'status' =>
                'rejected',

            'approved_by' =>
                auth()->id(),

            'approved_at' =>
                now(),

            'remarks' =>
                $request->remarks
                ??
                'Payment rejected by administrator.',
        ]);

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
                'message' => 'PayMongo secret key is not configured.',
            ];
        }

        if (
            $checkoutSessionId === ''
            || !Str::startsWith($checkoutSessionId, 'cs_')
        ) {
            return [
                'paid' => false,
                'payment_id' => null,
                'message' => 'This transaction does not have a valid PayMongo Checkout Session reference.',
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
                    . rawurlencode($checkoutSessionId)
                );

            if ($response->failed()) {
                $detail = data_get(
                    $response->json(),
                    'errors.0.detail'
                )
                    ?? (
                        'PayMongo returned HTTP '
                        . $response->status()
                    );

                return [
                    'paid' => false,
                    'payment_id' => null,
                    'message' => 'PathWise could not verify the PayMongo checkout yet: ' . $detail,
                ];
            }

            $session = $response->json('data');

            $returnedSessionId = (string) data_get(
                $session,
                'id',
                ''
            );

            $referenceNumber = (string) data_get(
                $session,
                'attributes.reference_number',
                ''
            );

            if (
                !hash_equals(
                    $checkoutSessionId,
                    $returnedSessionId
                )
                || !hash_equals(
                    (string) $transaction->transaction_no,
                    $referenceNumber
                )
            ) {
                return [
                    'paid' => false,
                    'payment_id' => null,
                    'message' => 'PayMongo returned a checkout session that does not match this PathWise transaction.',
                ];
            }

            $payments = data_get(
                $session,
                'attributes.payments',
                []
            );

            if (!is_array($payments)) {
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
                        'payment_id' => data_get(
                            $payment,
                            'id'
                        ),
                        'message' => 'PayMongo confirmed the payment successfully.',
                    ];
                }
            }

            return [
                'paid' => false,
                'payment_id' => null,
                'message' => 'PayMongo has not returned a completed paid payment for this transaction yet.',
            ];
        } catch (\Throwable $e) {
            report($e);

            return [
                'paid' => false,
                'payment_id' => null,
                'message' => 'PathWise could not contact PayMongo to verify the payment. Please try again.',
            ];
        }
    }

    private function approveVerifiedPayMongoTransaction(
        Transaction $transaction,
        ?string $paymentId = null
    ): void {
        DB::transaction(function () use (
            $transaction,
            $paymentId
        ) {
            $remarks = 'Payment securely verified through PayMongo.';

            if ($paymentId) {
                $remarks .= ' Payment ID: ' . $paymentId;
            }

            /*
             * Keep payment_reference as cs_... so PathWise can re-query the
             * exact Checkout Session later if needed.
             */
            $transaction->update([
                'status' => 'approved',
                'payment_method' => 'PayMongo',
                'remarks' => Str::limit($remarks, 1000),
                'approved_at' => now(),
            ]);

            $this->activateEnrollment(
                $transaction->student_id,
                $transaction->course_id
            );
        });

        $this->notifyStudentAboutApprovedTransaction(
            $transaction->fresh()
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PAYMENT / ENROLLMENT NOTIFICATION
    |--------------------------------------------------------------------------
    | Notification failure must never undo a verified payment or enrollment,
    | so it runs only after the database transaction has completed.
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
                ||
                ! $course
                ||
                $transaction->status !== 'approved'
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


    private function activateEnrollment(
        int $studentId,
        int $courseId
    ): void {
        $enrollment = Enrollment::firstOrNew([
            'student_id' =>
                $studentId,

            'course_id' =>
                $courseId,
        ]);

        /*
         * Never downgrade a completed enrollment back to active when a paid
         * transaction page is revisited or a duplicate PayMongo webhook is
         * received.
         */
        $alreadyCompleted =
            $enrollment->exists
            &&
            strtolower(
                trim(
                    (string) $enrollment->status
                )
            )
            ===
            'completed';

        if (!$alreadyCompleted) {
            $enrollment->status =
                'active';
        }

        if (!$enrollment->enrolled_at) {
            $enrollment->enrolled_at =
                now();
        }

        if (
            $enrollment->progress_percentage
            ===
            null
        ) {
            $enrollment->progress_percentage =
                0;
        }

        $enrollment->save();
    }

    private function ensureStudentOwnsTransaction(
        Transaction $transaction
    ): void {
        if ((int) $transaction->student_id !== (int) auth()->id()) {
            abort(403, 'Unauthorized');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMONGO WEBHOOK SIGNATURE
    |--------------------------------------------------------------------------
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
                $request->header('Paymongo-Signature')
                ??
                $request->header('X-Paymongo-Signature')
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

        foreach (explode(',', $signatureHeader) as $part) {
            $pair = explode(
                '=',
                trim($part),
                2
            );

            if (count($pair) !== 2) {
                continue;
            }

            $key = trim($pair[0]);
            $value = trim($pair[1]);

            if (
                in_array(
                    $key,
                    ['t', 'te', 'li'],
                    true
                )
            ) {
                $signatureParts[$key] = $value;
            }
        }

        $timestamp =
            $signatureParts['t']
            ??
            '';

        if (
            $timestamp === ''
            ||
            !ctype_digit($timestamp)
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
                now()->timestamp
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
         * PayMongo uses "te" for test-mode webhooks and "li" for live mode.
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
            !hash_equals(
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

    private function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');

        $countToday = Transaction::whereDate(
            'created_at',
            now()->toDateString()
        )->count() + 1;

        return 'TRX-'
            . $date
            . '-'
            . str_pad(
                $countToday,
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}
