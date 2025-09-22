<?php

namespace Prasso\Church\Services;

use Prasso\Church\Models\Transaction;
use Prasso\Church\Models\Member;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialService
{
    /**
     * Record a new financial transaction.
     *
     * @param array $data
     * @return \Prasso\Church\Models\Transaction
     */
    public function recordTransaction(array $data): Transaction
    {
        return Transaction::create($data);
    }

    /**
     * Record a payment from Stripe.
     *
     * @param array $stripePayment
     * @param int|null $memberId
     * @return \Prasso\Church\Models\Transaction
     */
    public function recordStripePayment(array $stripePayment, ?int $memberId = null): Transaction
    {
        return $this->recordTransaction([
            'member_id' => $memberId,
            'reference_id' => $stripePayment['id'] ?? null,
            'amount' => $stripePayment['amount'] / 100, // Convert from cents
            'currency' => strtoupper($stripePayment['currency'] ?? 'USD'),
            'payment_method' => $stripePayment['payment_method_types'][0] ?? 'card',
            'transaction_type' => $this->determineTransactionType($stripePayment['metadata'] ?? []),
            'transaction_date' => now(),
            'status' => $stripePayment['status'] === 'succeeded' ? 'completed' : 'pending',
            'metadata' => $stripePayment,
        ]);
    }

    /**
     * Get a member's giving history.
     *
     * @param int $memberId
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMemberGivingHistory(int $memberId, array $filters = [])
    {
        $query = Transaction::where('member_id', $memberId)
            ->whereIn('transaction_type', ['tithe', 'offering', 'donation', 'pledge_payment'])
            ->orderBy('transaction_date', 'desc');

        if (isset($filters['start_date'])) {
            $query->where('transaction_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('transaction_date', '<=', $filters['end_date']);
        }

        if (isset($filters['type'])) {
            $query->where('transaction_type', $filters['type']);
        }

        return $query->get();
    }

    /**
     * Get financial summary for a given period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy
     * @return \Illuminate\Support\Collection
     */
    public function getFinancialSummary(Carbon $startDate, Carbon $endDate, string $groupBy = 'month')
    {
        $query = Transaction::query()
            ->select(
                DB::raw('SUM(amount) as total_amount'),
                'transaction_type',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('DATE(transaction_date) as date_group')
            )
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('transaction_type', 'date_group')
            ->orderBy('date_group');

        return $query->get()->groupBy(function ($item) use ($groupBy) {
            return Carbon::parse($item->date_group)->format($groupBy === 'month' ? 'Y-m' : 'Y-m-d');
        });
    }

    /**
     * Generate a contribution statement for a member.
     *
     * @param int $memberId
     * @param int $year
     * @return array
     */
    public function generateContributionStatement(int $memberId, int $year): array
    {
        $member = Member::findOrFail($memberId);
        
        $transactions = Transaction::where('member_id', $memberId)
            ->whereYear('transaction_date', $year)
            ->where('status', 'completed')
            ->orderBy('transaction_date')
            ->get();

        $summary = [
            'year' => $year,
            'member' => $member->only(['id', 'first_name', 'last_name', 'email']),
            'total_given' => $transactions->sum('amount'),
            'transactions' => $transactions,
            'by_type' => $transactions->groupBy('transaction_type')->map->sum('amount'),
            'by_month' => $transactions->groupBy(fn($t) => $t->transaction_date->format('F'))->map->sum('amount'),
        ];

        return $summary;
    }

    /**
     * Determine transaction type from metadata.
     *
     * @param array $metadata
     * @return string
     */
    protected function determineTransactionType(array $metadata): string
    {
        return $metadata['type'] ?? 'donation';
    }
}
