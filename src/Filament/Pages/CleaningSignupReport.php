<?php

namespace Prasso\Church\Filament\Pages;

use Filament\Pages\Page;
use Prasso\Church\Models\VolunteerPosition;
use Prasso\Church\Models\VolunteerAssignment;

class CleaningSignupReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Cleaning Signup Report';
    protected static ?string $navigationGroup = 'Church';
    protected static string $view = 'church::filament.pages.cleaning-signup-report';

    public ?VolunteerPosition $position = null;
    public array $assignments = [];

    public function mount(): void
    {
        $this->position = VolunteerPosition::where('title', 'Clean the Church')->first();

        if ($this->position) {
            $this->assignments = VolunteerAssignment::where('position_id', $this->position->id)
                ->whereIn('status', ['pending', 'active'])
                ->where(function ($query) {
                    // Show assignments with start_date from today onwards
                    $query->whereNull('start_date')
                          ->orWhere('start_date', '>=', now()->startOfDay());
                })
                ->with('member')
                ->orderBy('start_date')
                ->get()
                ->map(function ($assignment) {
                    $startDate = $assignment->start_date;
                    $weekNumber = $assignment->metadata['preferred_week'] ?? null;
                    if (!$weekNumber && $startDate) {
                        $weekNumber = (int) $startDate->copy()->subDays(3)->format('W');
                    }

                    $weekRange = null;
                    if ($startDate) {
                        $weekRange = $startDate->format('M j') . ' - ' . $startDate->copy()->addDays(2)->format('M j, Y');
                    }

                    return [
                        'member_name' => $assignment->member?->full_name
                            ?: ($assignment->metadata['signup_name'] ?? 'Unknown'),
                        'week_number' => $weekNumber,
                        'week_range' => $weekRange,
                        'status' => $assignment->status,
                        'notes' => $assignment->notes,
                    ];
                })
                ->toArray();
        }
    }
}
