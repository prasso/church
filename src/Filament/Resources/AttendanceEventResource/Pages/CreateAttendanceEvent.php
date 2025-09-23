<?php

namespace Prasso\Church\Filament\Resources\AttendanceEventResource\Pages;

use Prasso\Church\Filament\Resources\AttendanceEventResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAttendanceEvent extends CreateRecord
{
    protected static string $resource = AttendanceEventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = Auth::id();
        $data['created_by'] = $userId;
        $data['updated_by'] = $userId;
        return $data;
    }
}
