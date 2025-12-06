<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DiscountCodeController extends Controller
{
    /**
     * Validate a discount code
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'order_total' => 'required|numeric|min:0',
        ]);

        $code = DiscountCode::where('code', strtoupper($request->code))
            ->active()
            ->first();

        if (!$code) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid discount code.',
            ], 404);
        }

        $user = Auth::user();
        $orderTotal = $request->order_total;

        $validation = $code->isValid($user, $orderTotal);

        if (!$validation['valid']) {
            return response()->json($validation, 422);
        }

        $discountAmount = $code->calculateDiscount($orderTotal);
        $newTotal = max(0, $orderTotal - $discountAmount);

        return response()->json([
            'valid' => true,
            'message' => 'Discount code applied successfully!',
            'discount_code_id' => $code->id,
            'discount_amount' => $discountAmount,
            'new_total' => $newTotal,
            'discount_type' => $code->type,
            'discount_value' => $code->value,
            'formatted_value' => $code->formatted_value,
        ], 200);
    }
}
