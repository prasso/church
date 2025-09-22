<?php

namespace Prasso\Church\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Pledge;
use Prasso\Church\Models\Transaction;

class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a test member
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $member = Member::firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'membership_date' => now(),
            ]
        );

        // Create some test pledges
        $pledges = [
            [
                'campaign_name' => 'Building Fund 2025',
                'description' => 'Annual building maintenance fund',
                'amount' => 1000.00,
                'frequency' => 'monthly',
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'payment_method' => 'credit_card',
            ],
            [
                'campaign_name' => 'Missions 2025',
                'description' => 'Support for international missions',
                'amount' => 500.00,
                'frequency' => 'one_time',
                'start_date' => now(),
                'payment_method' => 'bank_transfer',
            ],
        ];

        foreach ($pledges as $pledgeData) {
            $pledge = $member->pledges()->create($pledgeData);
            
            // Create some transactions for each pledge
            if ($pledge->frequency === 'monthly') {
                $months = now()->diffInMonths($pledge->start_date) + 1;
                $months = min($months, 12); // Max 12 months
                
                for ($i = 0; $i < $months; $i++) {
                    $transactionDate = $pledge->start_date->copy()->addMonths($i);
                    
                    $transaction = Transaction::create([
                        'member_id' => $member->id,
                        'reference_id' => 'TXN' . now()->timestamp . $i,
                        'amount' => $pledge->amount / 12, // Monthly amount
                        'currency' => 'USD',
                        'payment_method' => $pledge->payment_method,
                        'transaction_type' => 'pledge_payment',
                        'transaction_date' => $transactionDate,
                        'status' => 'completed',
                        'metadata' => [
                            'pledge_id' => $pledge->id,
                            'campaign_name' => $pledge->campaign_name,
                            'description' => 'Monthly pledge payment',
                        ],
                    ]);
                    
                    // Update pledge with payment
                    $pledge->amount_paid += $transaction->amount;
                    $pledge->last_payment_date = $transactionDate;
                }
                
                $pledge->calculateNextPaymentDate();
                $pledge->save();
            } else {
                // One-time pledge
                $transaction = Transaction::create([
                    'member_id' => $member->id,
                    'reference_id' => 'TXN' . now()->timestamp . 'OT',
                    'amount' => $pledge->amount,
                    'currency' => 'USD',
                    'payment_method' => $pledge->payment_method,
                    'transaction_type' => 'pledge_payment',
                    'transaction_date' => $pledge->start_date,
                    'status' => 'completed',
                    'metadata' => [
                        'pledge_id' => $pledge->id,
                        'campaign_name' => $pledge->campaign_name,
                        'description' => 'One-time pledge payment',
                    ],
                ]);
                
                $pledge->amount_paid = $pledge->amount;
                $pledge->status = 'fulfilled';
                $pledge->last_payment_date = $pledge->start_date;
                $pledge->save();
            }
        }
        
        // Create some additional one-time donations
        $donations = [
            ['amount' => 50.00, 'type' => 'tithe', 'date' => now()->subDays(30)],
            ['amount' => 25.00, 'type' => 'offering', 'date' => now()->subDays(15)],
            ['amount' => 100.00, 'type' => 'donation', 'date' => now()->subDays(7)],
            ['amount' => 75.00, 'type' => 'tithe', 'date' => now()],
        ];
        
        foreach ($donations as $donation) {
            Transaction::create([
                'member_id' => $member->id,
                'reference_id' => 'DON' . now()->timestamp . rand(1000, 9999),
                'amount' => $donation['amount'],
                'currency' => 'USD',
                'payment_method' => 'credit_card',
                'transaction_type' => $donation['type'],
                'transaction_date' => $donation['date'],
                'status' => 'completed',
                'metadata' => [
                    'description' => ucfirst($donation['type']) . ' donation',
                    'category' => 'general',
                ],
            ]);
        }
    }
}
