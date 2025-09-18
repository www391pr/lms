<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\PaymentIntent;
use App\Models\Course;
use App\Models\Coupon;
use App\Models\CourseStudent;
use Stripe\Transfer;

class PaymentController extends Controller
{
    public function intent(Request $request){
        $request->validate([
            'course_id' => 'required|integer|exists:courses,id',
        ]);
        $courseId = $request->input('course_id');
        $course = Course::find($courseId);
        if (!$course->enabled) {
            return response()->json(['error' => 'This course is not available for enrollment.'], 403);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $student = auth()->user()->student;
        $couponCode = $request->input('coupon_code');

        $price = $course->price;
        if($price==0) {
            CourseStudent::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id' => $courseId,
                ],
                ['status' => 'enrolled']
            );
            return response()->json(['message' => 'Payment successful.']);
        }

        $price *= (100 - $course->discount) / 100;
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                })
                ->first();
            if (!$coupon) {
                return response()->json(['error' => 'Invalid or expired coupon'], 422);
            }

            if ($course->instructor_id !== $coupon->instructor_id) {
                return response()->json(['error' => 'Invalid or expired coupon'], 422);
            }
            $price *= (100 - $coupon->value) / 100;
        }
        try {
            $intent = PaymentIntent::create([
                'amount' => (int) round($price * 100),
                'currency' => 'usd',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'course_id' => $course->id,
                    'student_id' => $student->id,
                ]
            ]);

            return response()->json([
                'clientSecret' => $intent->client_secret
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function confirm(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|string',
        ]);

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_id);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid payment ID'], 400);
        }

        if ($paymentIntent->status !== 'succeeded') {
            return response()->json(['error' => 'Payment not successful'], 402);
        }

        $metadataCourseId = $paymentIntent->metadata->course_id;
        $metadataStudentId = $paymentIntent->metadata->student_id;
        $student = auth()->user()->student;

        if ((int) $metadataStudentId !== $student->id) {
            return response()->json(['error' => 'User mismatch'], 405);
        }

        DB::beginTransaction();
        try {
            CourseStudent::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id' => $metadataCourseId,
                ],
                ['status' => 'enrolled']
            );

            $course = Course::findOrFail($metadataCourseId);
            $instructor = $course->instructor;

            if ($instructor) {
                $amount = $paymentIntent->amount_received / 100; // Stripe returns cents
                $instructor->increment('current_balance', $amount*0.6);
                $instructor->increment('total_balance', $amount*0.6);
            }

            Payment::create([
                'payment_id' => $paymentIntent->id,
                'student_id' => $student->id,
                'course_id' => $metadataCourseId,
                'amount' => $amount,
                'status' => $paymentIntent->status,
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Database error: '.$e->getMessage()], 500);
        }

        return response()->json(['message' => 'Payment successful.']);
    }

    public function requestPayout(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',      // in dollars
            'note'   => 'nullable|string|max:255',
        ]);

        $instructor = $request->user()->instructor;
        $amount     = (float)$request->input('amount');

        if (!$instructor->stripe_account_id) {
                return response()->json(['error' => 'Instructor Stripe account missing'], 422);
        }

        if ($amount > $instructor->current_balance) {
            return response()->json(['error' => 'Insufficient balance'], 422);
        }

        if ($amount < 10) {
            return response()->json(['error' => 'Minimum payout is $10'], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $idempotencyKey = 'payout_'.Str::uuid()->toString();

        try {
            $transfer = null;

            DB::transaction(function () use ($instructor, $amount, $idempotencyKey, &$transfer, $request) {
                // 1) Create transfer to connected account (amount in cents)
                $transfer = Transfer::create(
                    [
                        'amount'      => (int)round($amount * 100),
                        'currency'    => 'usd',
                        'destination' => $instructor->stripe_account_id,
                        'description' => 'Instructor payout',
                        'metadata'    => [
                            'instructor_id' => $instructor->id,
                            'note' => (string)($request->input('note') ?? ''),
                        ],
                    ],
                    [
                        'idempotency_key' => $idempotencyKey, // protects against double charge
                    ]
                );

                // 2) Deduct from local balance
                $instructor->decrement('current_balance', $amount);

                // 3) Log payout
                Payout::create([
                    'instructor_id'     => $instructor->id,
                    'amount'            => $amount,
                    'status'            => 'paid',
                    'stripe_transfer_id'=> $transfer->id,
                    'idempotency_key'   => $idempotencyKey,
                ]);
            });

            return response()->json([
                'message'      => 'Payout successful',
                'transfer_id'  => $transfer->id,
                'amount'       => $amount,
                'currency'     => 'usd',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
//    public function startExpressOnboarding(Request $request)
//    {
//        $instructor = $request->user()->instructor;
//
//        Stripe::setApiKey(config('services.stripe.secret'));
//
//        if (!$instructor->stripe_account_id) {
//            $account = Account::create([
//                'type' => 'custom',       // or 'express'
//                'country' => 'US',
//                'business_type' => 'individual',
//                'capabilities' => [
//                    'card_payments' => ['requested' => true],
//                    'transfers'     => ['requested' => true],
//                ],
//                'business_profile' => [
//                    'mcc' => '8299',
//                ],
//                'tos_acceptance' => [
//                    'date' => time(),
//                    'ip'   => $request->ip(),
//                ],
//            ]);
//
//            Account::createExternalAccount(
//                $account->id,
//                ['external_account' => 'btok_us_verified']
//            );
//            $instructor->update(['stripe_account_id' => $account->id]);
//        }
//        $link = AccountLink::create([
//            'account' => $instructor->stripe_account_id,
//            'refresh_url' => config('app.url').'/connect/refresh',
//            'return_url'  => config('app.url').'/instructor/payments',
//            'type' => 'account_onboarding',
//        ]);
//
//        return response()->json(['url' => $link->url]);
//    }
    public function getPayouts(Request $request){

        $user = $request->user();
        if($user->isInstructor()) {
            $payouts = Payout::with('instructor')->where('instructor_id', $user->instructor->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else{
            $payouts = Payout::with('instructor')->orderBy('created_at', 'desc')->get();
        }

        return response()->json([
            'payouts' => $payouts,
        ]);
    }
}
