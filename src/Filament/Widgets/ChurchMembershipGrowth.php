<?php

namespace Prasso\Church\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\AttendanceRecord;
use Prasso\Church\Models\Group;
use Carbon\Carbon;

class ChurchMembershipGrowth extends BaseWidget
{
    protected static ?string $pollingInterval = '300s'; // Update every 5 minutes

    protected function getStats(): array
    {
        // Calculate membership growth over the last 12 months
        $now = Carbon::now();
        $oneYearAgo = $now->copy()->subYear();

        $membersThisMonth = Member::where('created_at', '>=', $now->startOfMonth())->count();
        $membersLastMonth = Member::whereBetween('created_at', [
            $now->copy()->subMonth()->startOfMonth(),
            $now->copy()->subMonth()->endOfMonth()
        ])->count();

        $growthPercent = $membersLastMonth > 0
            ? round((($membersThisMonth - $membersLastMonth) / $membersLastMonth) * 100, 1)
            : ($membersThisMonth > 0 ? 100 : 0);

        // Active members (those with attendance in last 30 days)
        $activeMembers = AttendanceRecord::where('check_in_time', '>=', $now->subDays(30))
            ->distinct('member_id')
            ->count('member_id');

        $totalMembers = Member::count();
        $activityRate = $totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100, 1) : 0;

        // Group participation
        $totalMembersInGroups = DB::table('chm_group_member')->distinct('member_id')->count('member_id');
        $groupParticipationRate = $totalMembers > 0 ? round(($totalMembersInGroups / $totalMembers) * 100, 1) : 0;

        // Average attendance trend (last 4 weeks)
        $attendanceTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = $now->copy()->subWeeks($i)->startOfWeek();
            $weekEnd = $now->copy()->subWeeks($i)->endOfWeek();

            $weeklyAttendance = AttendanceRecord::whereBetween('check_in_time', [$weekStart, $weekEnd])->count();
            $attendanceTrend[] = $weeklyAttendance;
        }

        $avgAttendance = count($attendanceTrend) > 0 ? round(array_sum($attendanceTrend) / count($attendanceTrend)) : 0;
        $lastWeekAttendance = end($attendanceTrend);
        $attendanceChange = $avgAttendance > 0
            ? round((($lastWeekAttendance - $avgAttendance) / $avgAttendance) * 100, 1)
            : 0;

        return [
            Stat::make('Membership Growth', $membersThisMonth . ' this month')
                ->description($growthPercent >= 0 ? "+{$growthPercent}% from last month" : "{$growthPercent}% from last month")
                ->descriptionIcon($growthPercent >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-user-plus')
                ->color($growthPercent >= 0 ? 'success' : 'danger'),

            Stat::make('Active Members', $activeMembers)
                ->description("{$activityRate}% activity rate (30 days)")
                ->icon('heroicon-o-users')
                ->color('primary')
                ->url(route('filament.site-admin.resources.members.index')),

            Stat::make('Group Participation', $totalMembersInGroups . ' members')
                ->description("{$groupParticipationRate}% in groups")
                ->icon('heroicon-o-user-group')
                ->color('info')
                ->url(route('filament.site-admin.resources.groups.index')),

            Stat::make('Weekly Attendance', $lastWeekAttendance)
                ->description($attendanceChange >= 0 ? "+{$attendanceChange}% vs average" : "{$attendanceChange}% vs average")
                ->descriptionIcon($attendanceChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->icon('heroicon-o-calendar-days')
                ->color($attendanceChange >= 0 ? 'success' : 'warning'),
        ];
    }
}
