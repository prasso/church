<?php

namespace Prasso\Church\Filament\Resources\GroupResource\Pages;

use Prasso\Church\Filament\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
