<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('size')
                    ->maxLength(50)
                    ->nullable(),
                Forms\Components\TextInput::make('color')
                    ->maxLength(50)
                    ->nullable(),
                Forms\Components\TextInput::make('stock_quantity')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('additional_price')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('size')
            ->columns([
                Tables\Columns\TextColumn::make('size'),
                Tables\Columns\TextColumn::make('color'),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('additional_price')
                    ->money('PHP')
                    ->sortable(),
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
