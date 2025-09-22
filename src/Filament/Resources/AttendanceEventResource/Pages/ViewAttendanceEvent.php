<?php

namespace Prasso\Church\Filament\Resources\AttendanceEventResource\Pages;

use Prasso\Church\Filament\Resources\AttendanceEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAttendanceEvent extends ViewRecord
{
    protected static string $resource = AttendanceEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
