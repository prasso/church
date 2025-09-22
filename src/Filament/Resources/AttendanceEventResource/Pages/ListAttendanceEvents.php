<?php

namespace Prasso\Church\Filament\Resources\AttendanceEventResource\Pages;

use Prasso\Church\Filament\Resources\AttendanceEventResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceEvents extends ListRecords
{
    protected static string $resource = AttendanceEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
