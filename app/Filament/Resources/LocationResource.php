<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Filament\Resources\LocationResource\RelationManagers;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Humaidem\FilamentMapPicker\Fields\OSMMap;
use Filament\Forms\Components\Textarea;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationGroup = 'Office Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-c-map-pin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Location')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lokasi')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('office_id')
                            ->label('Lembaga')
                            ->relationship('office', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextArea::make('address')
                            ->label('Alamat')
                            ->required()
                            ->columnSpanFull()
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('Latitude-Longitude')
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('radius_m')
                            ->label('Radius (m)')
                            ->required()
                            ->numeric(),
                        OSMMap::make('location')
                            ->label('Maps')
                            ->columnSpanFull()
                            ->showMarker()
                            ->disabled()
                            ->hidden(fn (string $context): bool => $context !== 'view')
                            ->extraControl([
                                'zoomDelta'           => 1,
                                'zoomSnap'            => 0.25,
                                'wheelPxPerZoomLevel' => 60
                                ])
                            ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set, $record) {
                                if ($record) {
                                    $latitude = $record->latitude;
                                    $longitude = $record->longitude;

                                    if ($latitude && $longitude) {
                                        $set('location', ['lat' => $latitude, 'lng' => $longitude]);
                                        }
                                    }
                                })
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    $set('latitude', $state['lat']);
                                    $set('longitude', $state['lng']);
                                })
                            ->tilesUrl('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lokasi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('office.name')
                    ->label('Lembaga')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Alamat')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('radius_m')
                    ->label('Radius (m)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
