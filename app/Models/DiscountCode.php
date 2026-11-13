<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'type',
        'value',
        'max_discount_amount',
        'min_order_amount',
        'applicable_plan_slugs',
        'applicable_cycles',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'first_purchase_only',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'max_discount_amount' => 'integer',
            'min_order_amount' => 'integer',
            'applicable_plan_slugs' => 'array',
            'applicable_cycles' => 'array',
            'max_uses' => 'integer',
            'max_uses_per_user' => 'integer',
            'used_count' => 'integer',
            'first_purchase_only' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (DiscountCode $discount) {
            $discount->code = strtoupper(trim($discount->code));
        });
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isPercent(): bool
    {
        return $this->type === 'percent';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isNotStartedYet(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isCurrentlyUsable(): bool
    {
        return $this->is_active
            && ! $this->isExpired()
            && ! $this->isNotStartedYet()
            && ! $this->isExhausted();
    }

    public function appliesToPlan(string $planSlug): bool
    {
        $plans = array_values(array_filter((array) ($this->applicable_plan_slugs ?? [])));

        if ($plans === []) {
            return true;
        }

        return in_array($planSlug, $plans, true);
    }

    public function appliesToCycle(string $billingCycle): bool
    {
        $cycles = array_values(array_filter((array) ($this->applicable_cycles ?? [])));

        if ($cycles === []) {
            return true;
        }

        return in_array($billingCycle, $cycles, true);
    }

    public function calculateDiscount(int $payableBeforeDiscount): int
    {
        if ($payableBeforeDiscount <= 0) {
            return 0;
        }

        if ($this->isPercent()) {
            $percent = max(0, min(100, $this->value));
            $discount = (int) round(($payableBeforeDiscount * $percent) / 100);

            if ($this->max_discount_amount !== null && $this->max_discount_amount > 0) {
                $discount = min($discount, $this->max_discount_amount);
            }
        } else {
            $discount = $this->value;
        }

        return max(0, min($payableBeforeDiscount, $discount));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', function (Builder $q) use ($term) {
            $like = '%' . addcslashes(mb_substr($term, 0, 100), '%_\\') . '%';

            $q->where(function (Builder $sub) use ($like) {
                $sub->where('code', 'like', $like)
                    ->orWhere('title', 'like', $like);
            });
        });
    }
}
