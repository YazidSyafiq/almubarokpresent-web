<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-c-user-circle';

    public static function form(Form $form): Form
    {
        $schema = [
            Forms\Components\Section::make('Profile')
                ->collapsible()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->maxLength(255)
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context): bool => $context === 'create'),
                    Forms\Components\TextInput::make('phone')
                        ->label('No. Telp')
                        ->tel()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\FileUpload::make('image_url')
                        ->label('Photo')
                        ->columnSpanFull()
                        ->image(),
            ]),
        ];

        if (Auth::user()->hasRole('Super Admin')) {
            $schema[] =
                Forms\Components\Section::make('Office')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('group_id')
                            ->relationship('group', 'name')
                            ->preload()
                            ->searchable()
                            ->default(1)
                            ->required(fn (string $context): bool => $context === 'create')
                            ->hidden(fn () => !Auth::user()->hasRole('Super Admin')),
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->hidden(fn () => !Auth::user()->hasRole('Super Admin'))
                            ->default(3),
                    ]);
        }

        return $form->schema($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $is_super_admin = Auth::user()->hasRole('Super Admin');

                if (!$is_super_admin) {
                    $query->where('id', Auth::user()->id);
                }
            })
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Photo')
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->image_url ?? url('images/default_image.png')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telp')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('is_banned')
                    ->label('Banned Akun')
                    ->hidden(fn () => !Auth::user()->hasRole('Super Admin')),
                Tables\Columns\TextColumn::make('roles.name')
                    ->searchable()
                    ->hidden(fn () => !Auth::user()->hasRole('Super Admin')),
                Tables\Columns\TextColumn::make('group.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group.location.office.name')
                    ->label('Lembaga')
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
                ExportBulkAction::make()
                    ->label('Export User Data')
                    ->exports([
                        ExcelExport::make()
                        ->withColumns([
                            Column::make('name')
                                ->width(10)
                                ->heading('Nama'),
                            Column::make('email')
                                ->width(10)
                                ->heading('Email'),
                            Column::make('phone')
                                ->width(10)
                                ->heading('No. Telp'),
                            Column::make('group.name')
                                ->width(10)
                                ->heading('Kelas'),
                            Column::make('group.location.office.name')
                                ->width(10)
                                ->heading('Lembaga'),
                        ])
                        ->askForFilename(),
                    ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
