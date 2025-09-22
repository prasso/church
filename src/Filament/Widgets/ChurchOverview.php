<?php

namespace Prasso\Church\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Group;
use Prasso\Church\Models\Event;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\PastoralVisit;

class ChurchOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        // Total Members
        $totalMembers = Member::count();

        // Active Groups
        $activeGroups = Group::where('is_open', true)->count();

        // Upcoming Events (next 7 days)
        $upcomingEvents = Event::whereHas('occurrences', function ($query) {
            $query->where('start_date', '>=', now())
                  ->where('start_date', '<=', now()->addDays(7));
        })->count();

        // Pending Prayer Requests
        $pendingPrayerRequests = PrayerRequest::where('status', 'pending')->count();

        // Recent Pastoral Visits (last 30 days)
        $recentVisits = PastoralVisit::where('scheduled_for', '>=', now()->subDays(30))->count();

        return [
            Stat::make('Total Members', $totalMembers)
                ->description('Church membership')
                ->icon('heroicon-o-users')
                ->color('primary')
                ->url(route('filament.site-admin.resources.members.index')),

            Stat::make('Active Groups', $activeGroups)
                ->description('Small groups & ministries')
                ->icon('heroicon-o-user-group')
                ->color('success')
                ->url(route('filament.site-admin.resources.groups.index')),

            Stat::make('Upcoming Events', $upcomingEvents)
                ->description('Next 7 days')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->url(route('filament.site-admin.resources.events.index')),

            Stat::make('Pending Prayer Requests', $pendingPrayerRequests)
                ->description('Need attention')
                ->icon('heroicon-o-heart')
                ->color($pendingPrayerRequests > 0 ? 'danger' : 'gray')
                ->url(route('filament.site-admin.resources.prayer-requests.index')),
        ];
    }
}
