<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Transaction;
use Illuminate\Http\Request;
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
        /*
        |--------------------------------------------------------------------------
        | FREE COURSE
        |--------------------------------------------------------------------------
        */

        if ($course->price <= 0) {
            Enrollment::firstOrCreate(
                [
                    'student_id' => auth()->id(),
                    'course_id' => $course->id,
                ],
                [
                    'status' => 'active',
                    'enrolled_at' => now(),
                    'progress_percentage' => 0,
                ]
            );

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
        | CHECK EXISTING PENDING TRANSACTION
        |--------------------------------------------------------------------------
        */

        $existingPendingTransaction = Transaction::where('student_id', auth()->id())
            ->where('course_id', $course->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPendingTransaction) {
            return redirect()
                ->route(
                    'student.transactions.show',
                    $existingPendingTransaction
                )
                ->with(
                    'success',
                    'You already have a pending payment for this course.'
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
        |
        | PayMongo expects the amount in centavos.
        | Example:
        | ₱500.00 = 50000
        |
        */

        try {
            $response = Http::withBasicAuth(
                config('services.paymongo.secret_key'),
                ''
            )->post(
                'https://api.paymongo.com/v2/checkout_sessions',
                [
                    'data' => [
                        'attributes' => [
                            'line_items' => [
                                [
                                    'name' => $course->title,
                                    'amount' => (int) round($course->price * 100),
                                    'currency' => 'PHP',
                                    'quantity' => 1,
                                ],
                            ],

                            'payment_method_types' => [
                                'card',
                                'gcash',
                                'qrph',
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
                $transaction->update([
                    'status' => 'rejected',
                    'remarks' => 'Unable to create PayMongo checkout session.',
                ]);

                return back()->with(
                    'error',
                    'Unable to connect to PayMongo. Please try again.'
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

            /*
            |--------------------------------------------------------------------------
            | SAVE PAYMONGO CHECKOUT INFORMATION
            |--------------------------------------------------------------------------
            */

            $transaction->update([
                'payment_reference' => $checkoutSessionId,
                'payment_method' => 'PayMongo',
            ]);

            /*
            |--------------------------------------------------------------------------
            | REDIRECT STUDENT TO PAYMONGO
            |--------------------------------------------------------------------------
            */

            if (!$checkoutUrl) {
                return back()->with(
                    'error',
                    'PayMongo did not return a checkout URL.'
                );
            }

            return redirect()->away($checkoutUrl);

        } catch (\Throwable $e) {

            $transaction->update([
                'status' => 'rejected',
                'remarks' => 'PayMongo connection error.',
            ]);

            report($e);

            return back()->with(
                'error',
                'A payment error occurred. Please try again.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT SUCCESS PAGE
    |--------------------------------------------------------------------------
    */

    public function success(Transaction $transaction)
    {
        if ($transaction->student_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $transaction->load('course');

        return view(
            'student.transactions.success',
            compact('transaction')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMENT CANCEL PAGE
    |--------------------------------------------------------------------------
    */

    public function cancel(Transaction $transaction)
    {
        if ($transaction->student_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $transaction->load('course');

        return view(
            'student.transactions.cancel',
            compact('transaction')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | OLD TRANSACTION SHOW
    |--------------------------------------------------------------------------
    */

    public function studentShow(Transaction $transaction)
    {
        if ($transaction->student_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $transaction->load('course');

        return view(
            'student.transactions.upload-proof',
            compact('transaction')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PAYMONGO WEBHOOK
    |--------------------------------------------------------------------------
    |
    | This is the important part.
    |
    | PayMongo sends:
    |
    | checkout_session.payment.paid
    |
    | when payment succeeds.
    |
    */

    public function webhook(Request $request)
    {
        $event = $request->all();

        $eventType = data_get(
            $event,
            'data.attributes.type'
        );

        /*
        |--------------------------------------------------------------------------
        | ONLY PROCESS SUCCESSFUL CHECKOUT PAYMENTS
        |--------------------------------------------------------------------------
        */

        if ($eventType !== 'checkout_session.payment.paid') {
            return response()->json([
                'message' => 'Event ignored.',
            ], 200);
        }

        $session = data_get(
            $event,
            'data.attributes.data'
        );

        /*
        |--------------------------------------------------------------------------
        | GET TRANSACTION REFERENCE
        |--------------------------------------------------------------------------
        */

        $transactionNo = data_get(
            $session,
            'attributes.reference_number'
        );

        if (!$transactionNo) {
            return response()->json([
                'message' => 'Reference number missing.',
            ], 400);
        }

        /*
        |--------------------------------------------------------------------------
        | FIND PATHWISE TRANSACTION
        |--------------------------------------------------------------------------
        */

        $transaction = Transaction::where(
            'transaction_no',
            $transactionNo
        )->first();

        if (!$transaction) {
            return response()->json([
                'message' => 'Transaction not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | PREVENT DUPLICATE PROCESSING
        |--------------------------------------------------------------------------
        */

        if ($transaction->status === 'approved') {
            return response()->json([
                'message' => 'Transaction already processed.',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | GET PAYMENT INFORMATION
        |--------------------------------------------------------------------------
        */

        $payment = data_get(
            $session,
            'attributes.payments.0'
        );

        $paymentId = data_get(
            $payment,
            'id'
        );

        /*
        |--------------------------------------------------------------------------
        | APPROVE TRANSACTION
        |--------------------------------------------------------------------------
        */

        $transaction->update([
            'status' => 'approved',
            'payment_method' => 'PayMongo',
            'payment_reference' => $paymentId
                ?: $transaction->payment_reference,
            'remarks' => 'Payment successfully verified by PayMongo.',
            'approved_at' => now(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | ENROLL STUDENT
        |--------------------------------------------------------------------------
        */

        Enrollment::firstOrCreate(
            [
                'student_id' => $transaction->student_id,
                'course_id' => $transaction->course_id,
            ],
            [
                'status' => 'active',
                'enrolled_at' => now(),
                'progress_percentage' => 0,
            ]
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
        $transaction->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        Enrollment::firstOrCreate(
            [
                'student_id' => $transaction->student_id,
                'course_id' => $transaction->course_id,
            ],
            [
                'status' => 'active',
                'enrolled_at' => now(),
                'progress_percentage' => 0,
            ]
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
        $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $transaction->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'remarks' => $request->remarks
                ?? 'Payment rejected by administrator.',
        ]);

        return back()->with(
            'success',
            'Transaction rejected successfully.'
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

        return 'TRX-' .
            $date .
            '-' .
            str_pad(
                $countToday,
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}