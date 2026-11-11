<?php

namespace App\Filament\Resources\ReturnedProductResource\Pages;

use App\Filament\Resources\ReturnedProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReturnedProduct extends EditRecord
{
    protected static string $resource = ReturnedProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
