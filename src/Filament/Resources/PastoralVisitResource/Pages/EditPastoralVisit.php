<?php

namespace Prasso\Church\Filament\Resources\PastoralVisitResource\Pages;

use Prasso\Church\Filament\Resources\PastoralVisitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPastoralVisit extends EditRecord
{
    protected static string $resource = PastoralVisitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
