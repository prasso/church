<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Prasso\Church\Models\Transaction;
use Prasso\Church\Services\FinancialService;
use Prasso\Church\Http\Controllers\Controller;

class FinancialController extends Controller
{
    /**
     * The financial service instance.
     *
     * @var \Prasso\Church\Services\FinancialService
     */
    protected $financialService;

    /**
     * Create a new controller instance.
     *
     * @param  \Prasso\Church\Services\FinancialService  $financialService
     * @return void
     */
    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get a member's giving history.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGivingHistory(Request $request)
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|string|in:tithe,offering,donation,pledge_payment',
        ]);

        $transactions = $this->financialService->getMemberGivingHistory(
            $request->user()->id,
            $filters
        );

        return response()->json([
            'data' => $transactions,
            'total' => $transactions->sum('amount'),
            'count' => $transactions->count(),
        ]);
    }

    /**
     * Get financial summary for a given period.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFinancialSummary(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'nullable|string|in:day,week,month,year',
        ]);

        $summary = $this->financialService->getFinancialSummary(
            now()->parse($validated['start_date']),
            now()->parse($validated['end_date']),
            $validated['group_by'] ?? 'month'
        );

        return response()->json([
            'data' => $summary,
            'total' => $summary->sum('total_amount'),
        ]);
    }

    /**
     * Generate a contribution statement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateContributionStatement(Request $request, int $year = null)
    {
        $year = $year ?? now()->year;
        
        $statement = $this->financialService->generateContributionStatement(
            $request->user()->id,
            $year
        );

        return response()->json($statement);
    }

    /**
     * Record a manual transaction.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordTransaction(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'payment_method' => 'required|string',
            'transaction_type' => 'required|string|in:tithe,offering,donation,pledge_payment',
            'fund_id' => 'nullable|string',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $transaction = $this->financialService->recordTransaction(
            array_merge($validated, [
                'member_id' => $request->user()->id,
                'status' => 'completed',
            ])
        );

        return response()->json([
            'message' => 'Transaction recorded successfully',
            'data' => $transaction,
        ], 201);
    }
}
