<?php

namespace Prasso\Church\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Prasso\Church\Models\Pledge;
use Prasso\Church\Models\Transaction;
use Prasso\Church\Services\FinancialService;

class PledgeController extends Controller
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
     * Get all pledges for the authenticated member.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $status = $request->input('status');
        $campaign = $request->input('campaign');
        
        $query = $request->user()->pledges();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($campaign) {
            $query->where('campaign_name', $campaign);
        }
        
        $pledges = $query->latest()->paginate($request->input('per_page', 15));
        
        return response()->json([
            'data' => $pledges->items(),
            'meta' => [
                'total' => $pledges->total(),
                'per_page' => $pledges->perPage(),
                'current_page' => $pledges->currentPage(),
                'last_page' => $pledges->lastPage(),
            ],
        ]);
    }

    /**
     * Create a new pledge.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'frequency' => 'required|in:one_time,weekly,biweekly,monthly,quarterly,annually',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'payment_method' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $pledge = $request->user()->pledges()->create(
            array_merge($validated, [
                'status' => 'active',
                'next_payment_date' => $validated['start_date'],
            ])
        );

        return response()->json([
            'message' => 'Pledge created successfully',
            'data' => $pledge->load('member'),
        ], 201);
    }

    /**
     * Get a specific pledge.
     *
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Pledge $pledge)
    {
        $this->authorize('view', $pledge);
        
        return response()->json([
            'data' => $pledge->load(['member', 'transactions']),
        ]);
    }

    /**
     * Update a pledge.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Pledge $pledge)
    {
        $this->authorize('update', $pledge);
        
        $validated = $request->validate([
            'campaign_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|numeric|min:0.01',
            'currency' => 'sometimes|string|size:3',
            'frequency' => 'sometimes|in:one_time,weekly,biweekly,monthly,quarterly,annually',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'sometimes|in:active,fulfilled,cancelled,inactive',
            'payment_method' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $pledge->update($validated);
        
        return response()->json([
            'message' => 'Pledge updated successfully',
            'data' => $pledge->fresh(),
        ]);
    }

    /**
     * Record a payment against a pledge.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Http\JsonResponse
     */
    public function recordPayment(Request $request, Pledge $pledge)
    {
        $this->authorize('update', $pledge);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $pledge->remaining_balance,
            'transaction_id' => 'required|string|max:255|unique:chm_transactions,reference_id',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        
        $transaction = null;
        
        // Use a database transaction to ensure data consistency
        DB::transaction(function () use ($pledge, $validated, &$transaction) {
            // Record the payment against the pledge
            $pledge->recordPayment(
                $validated['amount'],
                $validated['transaction_id']
            );
            
            // Create a transaction record
            $transaction = Transaction::create([
                'member_id' => $pledge->member_id,
                'reference_id' => $validated['transaction_id'],
                'amount' => $validated['amount'],
                'currency' => $pledge->currency,
                'payment_method' => $pledge->payment_method,
                'transaction_type' => 'pledge_payment',
                'transaction_date' => $validated['payment_date'] ?? now(),
                'status' => 'completed',
                'metadata' => [
                    'pledge_id' => $pledge->id,
                    'campaign_name' => $pledge->campaign_name,
                    'notes' => $validated['notes'] ?? null,
                ],
            ]);
        });
        
        return response()->json([
            'message' => 'Payment recorded successfully',
            'data' => [
                'pledge' => $pledge->fresh(),
                'transaction' => $transaction,
            ],
        ]);
    }

    /**
     * Get transactions for a specific pledge.
     *
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactions(Pledge $pledge)
    {
        $this->authorize('view', $pledge);
        
        $transactions = $pledge->transactions()
            ->orderBy('transaction_date', 'desc')
            ->paginate(15);
        
        return response()->json([
            'data' => $transactions->items(),
            'meta' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * Delete a pledge.
     *
     * @param  \Prasso\Church\Models\Pledge  $pledge
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Pledge $pledge)
    {
        $this->authorize('delete', $pledge);
        
        $pledge->delete();
        
        return response()->json([
            'message' => 'Pledge deleted successfully',
        ]);
    }
}
