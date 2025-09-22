<?php

namespace Prasso\Church\Filament\Resources\PrayerRequestResource\Pages;

use Prasso\Church\Filament\Resources\PrayerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrayerRequests extends ListRecords
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('printAll')
                ->label('Print All')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('church.prayer-requests.print-all'))
                ->openUrlInNewTab(),
            Actions\Action::make('downloadAllText')
                ->label('Download All as Text')
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('church.prayer-requests.print-all', ['format' => 'text']))
                ->openUrlInNewTab(),
            Actions\Action::make('printSms')
                ->label('Print SMS Requests')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->url(fn () => route('church.prayer-requests.print-sms'))
                ->openUrlInNewTab(),
            Actions\Action::make('downloadSmsText')
                ->label('Download SMS as Text')
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('church.prayer-requests.print-sms', ['format' => 'text']))
                ->openUrlInNewTab(),
        ];
    }
}
