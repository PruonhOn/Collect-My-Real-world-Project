<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-archive-box';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventory Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->default(auth()->id()),
                Forms\Components\TextInput::make('reference_no')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->default('PO-' . date('Ymd') . '-' . rand(1000, 9999)),
                Forms\Components\TextInput::make('total_amount')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\TextInput::make('paid_amount')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱'),
                Forms\Components\DateTimePicker::make('purchase_date')
                    ->default(now()),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Forms\Components\Textarea::make('notes')
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_no')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_amount')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
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
            PurchaseResource\RelationManagers\PurchaseItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'view' => Pages\ViewPurchase::route('/{record}'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }
}
