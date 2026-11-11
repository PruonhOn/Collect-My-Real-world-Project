<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string | \UnitEnum | null $navigationGroup = 'Product Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->nullable(),
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->nullable(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('sku')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('barcode')
                    ->maxLength(100)
                    ->nullable(),
                Forms\Components\RichEditor::make('description')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cost_price')
                    ->required()
                    ->numeric()
                    ->prefix('₱'),
                Forms\Components\TextInput::make('selling_price')
                    ->required()
                    ->numeric()
                    ->prefix('₱'),
                Forms\Components\TextInput::make('stock_quantity')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('min_stock')
                    ->numeric()
                    ->default(5),
                Forms\Components\Toggle::make('status')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cost_price')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_quantity')
                    ->sortable()
                    ->color(fn (Product $record): string => $record->stock_quantity <= $record->min_stock ? 'danger' : 'success')
                    ->badge()
                    ->formatStateUsing(fn ($state, Product $record): string => $state . ($record->stock_quantity <= $record->min_stock ? ' (Low)' : '')),
                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('brand')
                    ->relationship('brand', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductResource\RelationManagers\VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
