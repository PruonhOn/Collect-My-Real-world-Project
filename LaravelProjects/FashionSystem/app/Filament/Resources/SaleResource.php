<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string | \UnitEnum | null $navigationGroup = 'Sales Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('invoice_no')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->default('INV-' . date('Ymd') . '-' . rand(1000, 9999)),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->nullable()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                    ]),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(auth()->id())
                    ->hidden(),
                Forms\Components\TextInput::make('subtotal')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\TextInput::make('discount')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\TextInput::make('tax')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\TextInput::make('total')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\TextInput::make('paid_amount')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'bank_transfer' => 'Bank Transfer',
                        'gcash' => 'GCash',
                    ]),
                Forms\Components\Select::make('payment_status')
                    ->options([
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'pending' => 'Pending',
                        'refunded' => 'Refunded',
                    ]),
                Forms\Components\DateTimePicker::make('sale_date')
                    ->default(now()),
                Forms\Components\Textarea::make('notes')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_no')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'partial',
                        'danger' => 'pending',
                        'gray' => 'refunded',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->options([
                        'paid' => 'Paid',
                        'partial' => 'Partial',
                        'pending' => 'Pending',
                        'refunded' => 'Refunded',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'cash' => 'Cash',
                        'card' => 'Card',
                        'bank_transfer' => 'Bank Transfer',
                        'gcash' => 'GCash',
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
            SaleResource\RelationManagers\SaleItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'view' => Pages\ViewSale::route('/{record}'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}
