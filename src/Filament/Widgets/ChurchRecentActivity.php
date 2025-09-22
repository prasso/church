<?php

namespace Prasso\Church\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\HtmlString;
use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\PastoralVisit;
use Carbon\Carbon;

class ChurchRecentActivity extends Widget
{
    protected static string $view = 'church::filament.widgets.church-recent-activity';

    protected int|string|array $columnSpan = 'full';

    public function getRecentAttendance()
    {
        return AttendanceRecord::with(['member', 'event'])
            ->latest('check_in_time')
            ->limit(5)
            ->get();
    }

    public function getRecentPrayerRequests()
    {
        return PrayerRequest::with('member')
            ->latest('created_at')
            ->limit(5)
            ->get();
    }

    public function getRecentVisits()
    {
        return PastoralVisit::with(['member', 'assignedTo'])
            ->latest('scheduled_for')
            ->limit(5)
            ->get();
    }
}
