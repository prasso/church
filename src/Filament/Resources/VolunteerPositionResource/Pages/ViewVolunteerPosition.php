<?php

namespace Prasso\Church\Filament\Resources\VolunteerPositionResource\Pages;

use Prasso\Church\Filament\Resources\VolunteerPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVolunteerPosition extends ViewRecord
{
    protected static string $resource = VolunteerPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
