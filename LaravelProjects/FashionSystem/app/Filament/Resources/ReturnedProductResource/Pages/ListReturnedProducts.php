<?php

namespace App\Filament\Resources\ReturnedProductResource\Pages;

use App\Filament\Resources\ReturnedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnedProducts extends ListRecords
{
    protected static string $resource = ReturnedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
