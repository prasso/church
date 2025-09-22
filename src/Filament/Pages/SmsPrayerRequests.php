<?php

namespace Prasso\Church\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Facades\FilamentIcon;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Route;

class SmsPrayerRequests extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    
    protected static ?string $navigationGroup = 'Church Management';
    
    protected static ?int $navigationSort = 41;
    
    protected static string $view = 'church::filament.pages.sms-prayer-requests';
    
    public static function getNavigationLabel(): string
    {
        return __('SMS Prayer Requests');
    }
    
    public static function getNavigationBadge(): ?string
    {
        return \Prasso\Church\Models\PrayerRequest::fromSms()
            ->where('status', 'pending')
            ->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): string
    {
        return \Prasso\Church\Models\PrayerRequest::fromSms()
            ->where('status', 'pending')
            ->count() ? 'warning' : 'primary';
    }
    
    public function mount(): void
    {
        // Redirect to the prayer requests page with the SMS filter applied
        redirect()->to(route('filament.site-admin.resources.prayer-requests.index', [
            'tableFilters[from_sms]' => true,
        ]));
    }
}
