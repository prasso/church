<?php

namespace Prasso\Church\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pledge extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'aph_pledges';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'member_id',
        'campaign_name',
        'description',
        'amount',
        'amount_paid',
        'currency',
        'frequency',
        'start_date',
        'end_date',
        'status',
        'last_payment_date',
        'next_payment_date',
        'payment_method',
        'payment_reference',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_payment_date' => 'date',
        'next_payment_date' => 'date',
        'metadata' => 'array',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'active',
        'frequency' => 'one_time',
        'currency' => 'USD',
        'amount_paid' => 0,
    ];

    /**
     * Get the member that owns the pledge.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Get the remaining balance of the pledge.
     *
     * @return float
     */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0, $this->amount - $this->amount_paid);
    }

    /**
     * Get the progress percentage of the pledge.
     *
     * @return float
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->amount <= 0) {
            return 0;
        }

        return min(100, round(($this->amount_paid / $this->amount) * 100, 2));
    }

    /**
     * Check if the pledge is fully paid.
     *
     * @return bool
     */
    public function isFullyPaid(): bool
    {
        return $this->amount_paid >= $this->amount;
    }

    /**
     * Record a payment against this pledge.
     *
     * @param float $amount
     * @param string $transactionId
     * @return bool
     */
    public function recordPayment(float $amount, string $transactionId): bool
    {
        $this->amount_paid += $amount;
        $this->last_payment_date = now();
        
        if ($this->isFullyPaid()) {
            $this->status = 'fulfilled';
            $this->next_payment_date = null;
        } else {
            $this->calculateNextPaymentDate();
        }

        // Record the transaction
        $transaction = new Transaction([
            'member_id' => $this->member_id,
            'reference_id' => $transactionId,
            'amount' => $amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'transaction_type' => 'pledge_payment',
            'metadata' => [
                'pledge_id' => $this->id,
                'campaign_name' => $this->campaign_name,
            ],
        ]);

        return $this->save() && $transaction->save();
    }

    /**
     * Calculate the next payment date based on frequency.
     *
     * @return $this
     */
    public function calculateNextPaymentDate(): self
    {
        if ($this->frequency === 'one_time' || $this->isFullyPaid()) {
            $this->next_payment_date = null;
            return $this;
        }

        $lastDate = $this->last_payment_date ?? $this->start_date;
        
        $this->next_payment_date = match ($this->frequency) {
            'weekly' => $lastDate->addWeek(),
            'biweekly' => $lastDate->addWeeks(2),
            'monthly' => $lastDate->addMonth(),
            'quarterly' => $lastDate->addMonths(3),
            'annually' => $lastDate->addYear(),
            default => null,
        };

        return $this;
    }

    /**
     * Scope a query to only include active pledges.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    /**
     * Scope a query to only include pledges due for payment.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDueForPayment($query)
    {
        return $query->active()
                    ->where(function($q) {
                        $q->whereNull('next_payment_date')
                          ->orWhere('next_payment_date', '<=', now());
                    });
    }
}
