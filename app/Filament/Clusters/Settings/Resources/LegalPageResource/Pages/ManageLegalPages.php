<?php

namespace App\Filament\Clusters\Settings\Resources\LegalPageResource\Pages;

use App\Filament\Clusters\Settings\Resources\LegalPageResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Actions;

class ManageLegalPages extends ManageRecords
{
    protected static string $resource = LegalPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


