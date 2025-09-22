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
                ->action(function () {
                    $records = \Prasso\Church\Models\PrayerRequest::all();
                    $content = "PRAYER REQUESTS\n";
                    $content .= "Generated: " . now()->format('F j, Y g:i A') . "\n";
                    $content .= "Total: " . $records->count() . "\n\n";
                    
                    foreach ($records as $index => $record) {
                        $content .= \Prasso\Church\Filament\Resources\PrayerRequestResource::generateTextContent($record);
                        $content .= "\n" . str_repeat('-', 40) . "\n\n";
                    }
                    
                    return response($content)
                        ->header('Content-Type', 'text/plain')
                        ->header('Content-Disposition', 'attachment; filename="all-prayer-requests.txt"');
                }),
            Actions\Action::make('printSms')
                ->label('Print SMS Requests')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->url(fn () => route('church.prayer-requests.print-sms'))
                ->openUrlInNewTab(),
            Actions\Action::make('downloadSmsText')
                ->label('Download SMS Requests as Text')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->action(function () {
                    $records = \Prasso\Church\Models\PrayerRequest::fromSms()->get();
                    $content = "SMS PRAYER REQUESTS\n";
                    $content .= "Generated: " . now()->format('F j, Y g:i A') . "\n";
                    $content .= "Total: " . $records->count() . "\n\n";
                    
                    foreach ($records as $index => $record) {
                        $content .= \Prasso\Church\Filament\Resources\PrayerRequestResource::generateTextContent($record);
                        $content .= "\n" . str_repeat('-', 40) . "\n\n";
                    }
                    
                    return response($content)
                        ->header('Content-Type', 'text/plain')
                        ->header('Content-Disposition', 'attachment; filename="sms-prayer-requests.txt"');
                }),
        ];
    }
}
