<?php

namespace Prasso\Church\Filament\Resources\VolunteerPositionResource\Pages;

use Prasso\Church\Filament\Resources\VolunteerPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVolunteerPosition extends EditRecord
{
    protected static string $resource = VolunteerPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
