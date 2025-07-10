<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OfficeResource\Pages;
use App\Filament\Resources\OfficeResource\RelationManagers;
use App\Models\Office;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Humaidem\FilamentMapPicker\Fields\OSMMap;
use Filament\Forms\Components\Textarea;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationGroup = 'Office Management';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'heroicon-m-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profile Office')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Lembaga')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('yayasan')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('No. Telp')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextArea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Lembaga')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('yayasan')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->wrap()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'view' => Pages\ViewOffice::route('/{record}'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }
}
