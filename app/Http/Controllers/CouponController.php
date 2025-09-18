<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Course;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'value' => 'required|numeric|min:0|max:100',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $instructor = auth()->user()->instructor;

        $coupon = $instructor->coupons()->create([
            'code' => $request->code,
            'value' => $request->value,
            'expires_at' => $request->expires_at,
            'is_active' => true,
        ]);

        return response()->json(['message' => 'Coupon created successfully', 'coupon' => $coupon]);
    }

    public function applyCoupon(Request $request, Course $course)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $couponCode = $request->input('coupon_code');

        $coupon = Coupon::where('code', $couponCode)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return response()->json(['message' => 'Invalid or expired coupon.'], 400);
        }

        if ($course->instructor_id !== $coupon->instructor_id) {
            return response()->json(['message' => 'This coupon is not valid for this course.'], 400);
        }

        $price = $course->price;
        $price *= (100 - $course->discount);
        $couponDiscount = ($price * $coupon->value)/100;
        $finalPrice = max(0, $price - $couponDiscount);

        return response()->json([
            'original_price' => $price,
            'discount' => $couponDiscount,
            'final_price' => $finalPrice,
            'discount_percentage' => $coupon->value
        ]);
    }

    public function enable(Coupon $coupon)
    {
        $coupon->update(['is_active' => true]);

        return response()->json([
            'message' => 'Coupon enabled successfully.',
            'coupon'  => $coupon,
        ]);
    }

    public function disable(Coupon $coupon)
    {
        $coupon->update(['is_active' => false]);

        return response()->json([
            'message' => 'Coupon disabled successfully.',
            'coupon'  => $coupon,
        ]);
    }

    public function getInstructorCoupons()
    {
        $user = auth()->user();

        if (!$user->isInstructor()) {
            return response()->json([
                'message' => 'Only instructors can view coupons.'
            ], 403);
        }

        $coupons = Coupon::where('instructor_id', $user->instructor->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'code', 'value', 'expires_at', 'is_active']);

        return response()->json([
            'message' => 'Coupons retrieved successfully.',
            'coupons' => $coupons,
        ]);
    }
}
