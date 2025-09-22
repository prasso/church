<?php

namespace Prasso\Church\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Prasso\Church\Models\PrayerRequest;
use Illuminate\Support\Facades\URL;

class SmsPrayerRequestsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $totalSmsRequests = PrayerRequest::fromSms()->count();
        $recentSmsRequests = PrayerRequest::fromSms()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $pendingSmsRequests = PrayerRequest::fromSms()
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('SMS Prayer Requests', $totalSmsRequests)
                ->description('Total prayer requests received via SMS')
                ->descriptionIcon('heroicon-m-chat-bubble-left-ellipsis')
                ->color('warning')
                ->chart([
                    PrayerRequest::fromSms()->where('created_at', '>=', now()->subDays(30))->count() / 4,
                    PrayerRequest::fromSms()->where('created_at', '>=', now()->subDays(21))->count() / 3,
                    PrayerRequest::fromSms()->where('created_at', '>=', now()->subDays(14))->count() / 2,
                    $recentSmsRequests,
                ])
                ->url(route('filament.site-admin.resources.prayer-requests.index', [
                    'tableFilters[from_sms]' => true,
                ])),
            
            Stat::make('Recent SMS Requests', $recentSmsRequests)
                ->description('Received in the last 7 days')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success')
                ->url(route('filament.site-admin.resources.prayer-requests.index', [
                    'tableFilters[from_sms]' => true,
                    'tableFilters[recent]' => true,
                ])),
            
            Stat::make('Pending SMS Requests', $pendingSmsRequests)
                ->description('Awaiting response')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingSmsRequests > 0 ? 'danger' : 'success')
                ->url(route('filament.site-admin.resources.prayer-requests.index', [
                    'tableFilters[from_sms]' => true,
                    'tableFilters[status]' => 'pending',
                ])),
        ];
    }

    public function getActions(): array
    {
        return [
            \Filament\Actions\Action::make('viewAll')
                ->label('View All SMS Requests')
                ->url(route('filament.site-admin.resources.prayer-requests.index', [
                    'tableFilters[from_sms]' => true,
                ])),
            \Filament\Actions\Action::make('printAll')
                ->label('Print SMS Requests')
                ->url(route('church.prayer-requests.print-sms'))
                ->openUrlInNewTab(),
            \Filament\Actions\Action::make('downloadText')
                ->label('Download as Text')
                ->url(route('church.prayer-requests.print-sms', ['format' => 'text']))
                ->openUrlInNewTab(),
        ];
    }
}
