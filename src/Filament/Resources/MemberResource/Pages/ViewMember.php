<?php

namespace Prasso\Church\Filament\Resources\MemberResource\Pages;

use Prasso\Church\Filament\Resources\MemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMember extends ViewRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
