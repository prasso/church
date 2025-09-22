<?php

namespace Prasso\Church\Filament\Resources\PastoralVisitResource\Pages;

use Prasso\Church\Filament\Resources\PastoralVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPastoralVisit extends ViewRecord
{
    protected static string $resource = PastoralVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
