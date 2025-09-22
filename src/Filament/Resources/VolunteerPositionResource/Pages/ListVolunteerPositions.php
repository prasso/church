<?php

namespace Prasso\Church\Filament\Resources\VolunteerPositionResource\Pages;

use Prasso\Church\Filament\Resources\VolunteerPositionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVolunteerPositions extends ListRecords
{
    protected static string $resource = VolunteerPositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
