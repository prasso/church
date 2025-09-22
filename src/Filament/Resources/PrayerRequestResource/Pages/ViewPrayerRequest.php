<?php

namespace Prasso\Church\Filament\Resources\PrayerRequestResource\Pages;

use Prasso\Church\Filament\Resources\PrayerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPrayerRequest extends ViewRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
