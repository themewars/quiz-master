<?php

namespace App\Filament\Clusters\Settings\Resources\LegalPageResource\Pages;

use App\Filament\Clusters\Settings\Resources\LegalPageResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLegalPages extends ManageRecords
{
    protected static string $resource = LegalPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Resources\Pages\ListRecords\CreateAction::make(),
        ];
    }
}


