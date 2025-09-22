<?php

namespace Prasso\Church\Filament\Resources\GroupResource\Pages;

use Prasso\Church\Filament\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGroup extends ViewRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
