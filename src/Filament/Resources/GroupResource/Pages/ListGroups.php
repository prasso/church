<?php

namespace Prasso\Church\Filament\Resources\GroupResource\Pages;

use Prasso\Church\Filament\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroups extends ListRecords
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
