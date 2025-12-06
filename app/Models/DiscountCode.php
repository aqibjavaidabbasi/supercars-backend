<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'times_used',
        'expires_at',
        'is_active',
        'one_time_per_user',
        'min_order_value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'one_time_per_user' => 'boolean',
        'min_order_value' => 'decimal:2',
    ];

    /**
     * Get the users who have used this discount code
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'discount_code_user')
            ->withPivot('order_id')
            ->withTimestamps();
    }

    /**
     * Get the orders that used this discount code
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if the discount code is valid
     */
    public function isValid(User $user = null, $orderTotal = 0): array
    {
        // Check if code is active
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This discount code is not active.'];
        }

        // Check if expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            return ['valid' => false, 'message' => 'This discount code has expired.'];
        }

        // Check max uses
        if ($this->max_uses !== null && $this->times_used >= $this->max_uses) {
            return ['valid' => false, 'message' => 'This discount code has reached its maximum usage limit.'];
        }

        // Check minimum order value
        if ($this->min_order_value && $orderTotal < $this->min_order_value) {
            return ['valid' => false, 'message' => 'Your order total must be at least £' . number_format($this->min_order_value, 2) . ' to use this code.'];
        }

        // Check one-time per user
        if ($user && $this->one_time_per_user) {
            $hasUsed = $this->users()->where('user_id', $user->id)->exists();
            if ($hasUsed) {
                return ['valid' => false, 'message' => 'You have already used this discount code.'];
            }
        }

        return ['valid' => true, 'message' => 'Discount code is valid.'];
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount($orderTotal): float
    {
        if ($this->type === 'percentage') {
            $discount = ($orderTotal * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        // Ensure discount doesn't exceed order total
        return min($discount, $orderTotal);
    }

    /**
     * Apply the discount code to a user
     */
    public function applyToUser(User $user, Order $order)
    {
        $this->increment('times_used');
        
        if ($this->one_time_per_user) {
            $this->users()->attach($user->id, ['order_id' => $order->id]);
        }
    }

    /**
     * Scope to get active codes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Get formatted discount value for display
     */
    public function getFormattedValueAttribute()
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }
        return '£' . number_format($this->value, 2);
    }
}
