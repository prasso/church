<?php

namespace Prasso\Church\Filament\Resources\AttendanceEventResource\Pages;

use Prasso\Church\Filament\Resources\AttendanceEventResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceEvent extends EditRecord
{
    protected static string $resource = AttendanceEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
