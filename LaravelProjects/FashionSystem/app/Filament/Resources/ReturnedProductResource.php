<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnedProductResource\Pages;
use App\Models\ReturnedProduct;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnedProductResource extends Resource
{
    protected static ?string $model = ReturnedProduct::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-uturn-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Sales Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('sale_id')
                    ->relationship('sale', 'invoice_no')
                    ->required()
                    ->searchable(),
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
                Forms\Components\Textarea::make('reason')
                    ->nullable()
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Forms\Components\TextInput::make('refund_amount')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(auth()->id())
                    ->hidden(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale.invoice_no')
                    ->label('Sale')
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('refund_amount')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnedProducts::route('/'),
            'create' => Pages\CreateReturnedProduct::route('/create'),
            'edit' => Pages\EditReturnedProduct::route('/{record}/edit'),
        ];
    }
}
