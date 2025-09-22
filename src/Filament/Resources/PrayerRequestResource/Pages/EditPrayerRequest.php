<?php

namespace Prasso\Church\Filament\Resources\PrayerRequestResource\Pages;

use Prasso\Church\Filament\Resources\PrayerRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrayerRequest extends EditRecord
{
    protected static string $resource = PrayerRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
