<?php

namespace Prasso\Church\Filament\Resources\PastoralVisitResource\Pages;

use Prasso\Church\Filament\Resources\PastoralVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPastoralVisits extends ListRecords
{
    protected static string $resource = PastoralVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
