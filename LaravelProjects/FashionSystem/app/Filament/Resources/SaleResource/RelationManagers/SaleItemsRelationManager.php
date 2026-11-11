<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SaleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('product_variant_id')
                    ->relationship('variant', 'id')
                    ->nullable()
                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->size ?: '') . ($record->size && $record->color ? ' - ' : '') . ($record->color ?: '')),
                    Forms\Components\TextInput::make('quantity')
                        ->required()
                        ->numeric(),
                    Forms\Components\TextInput::make('unit_price')
                        ->required()
                        ->numeric()
                        ->prefix('₱'),
                    Forms\Components\TextInput::make('subtotal')
                        ->required()
                        ->numeric()
                        ->prefix('₱'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('product.name'),
                Tables\Columns\TextColumn::make('variant.size')
                    ->label('Variant')
                    ->formatStateUsing(fn ($record) => ($record->variant?->size ?: '') . ($record->variant?->size && $record->variant?->color ? ' - ' : '') . ($record->variant?->color ?: '')),
                Tables\Columns\TextColumn::make('quantity'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->money('PHP'),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('PHP'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
