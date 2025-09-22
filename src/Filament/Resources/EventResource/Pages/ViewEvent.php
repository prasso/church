<?php

namespace Prasso\Church\Filament\Resources\EventResource\Pages;

use Prasso\Church\Filament\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
