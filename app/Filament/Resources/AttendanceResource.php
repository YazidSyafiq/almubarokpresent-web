<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use Illuminate\Support\Carbon;
use Filament\Forms\Get;
use Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationGroup = 'Attendace Management';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationIcon = 'heroicon-c-finger-print';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Attendance Form')
                    ->collapsible()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->disabled()
                            ->label('Nama')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\TextInput::make('check_in_latitude')
                            ->label('Check In Latitude')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('check_out_latitude')
                            ->label('Check Out Latitude')
                            ->numeric(),
                        Forms\Components\TextInput::make('check_in_longitude')
                            ->label('Check In Longitude')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('check_out_longitude')
                            ->label('Check Out Longitude')
                            ->numeric(),
                        Forms\Components\TimePicker::make('check_in_time')
                            ->label('Waktu Masuk')
                            ->native(false)
                            ->required(),
                        Forms\Components\TimePicker::make('check_out_time')
                            ->native(false)
                            ->label('Waktu Pulang'),
                    ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                if ($user->hasRole('Super Admin')) {
                    // Super Admin dapat melihat semua data
                    return;
                }

                if ($user->hasRole('Ustad')) {
                    // Tampilkan semua attendance dari user yang berada dalam grup yang sama
                    $query->whereHas('user', function ($subQuery) use ($user) {
                        $subQuery->where('group_id', $user->group_id);
                    });
                } else {
                    // Tampilkan hanya attendance milik user saat ini
                    $query->where('user_id', $user->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.group.name')
                    ->label('Group')
                    ->searchable(),
                Tables\Columns\TextColumn::make('check_in_coordinates')
                    ->label('Koordinat Masuk'),
                Tables\Columns\TextColumn::make('check_in_time')
                    ->label('Waktu Masuk'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Tepat Waktu' => 'success',
                        'Terlambat' => 'danger',
                        'Data Tidak Lengkap'=> 'warning',
                    }),
                Tables\Columns\TextColumn::make('check_out_coordinates')
                    ->label('Koordinat Pulang')
                    ->formatStateUsing(function ($record) {
                        return ($record->check_out_latitude ?? false) && ($record->check_out_longitude ?? false)
                            ? "{$record->check_out_latitude}, {$record->check_out_longitude}"
                            : '';
                    }),
                Tables\Columns\TextColumn::make('check_out_time')
                    ->label('Waktu Pulang'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('startDate')
                            ->label('Start Date')
                            ->placeholder('Select start date')
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()), // Maksimum hingga endDate atau tanggal sekarang,
                        Forms\Components\DatePicker::make('endDate')
                            ->label('End Date')
                            ->placeholder('Select end date')
                            ->minDate(fn (Get $get) => $get('startDate') ?: now()) // Minimum mulai dari startDate atau tanggal sekarang
                            ->maxDate(now()), // Maksimum hingga tanggal sekarang
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                isset($data['startDate']) && $data['startDate'],
                                fn (Builder $query) => $query->whereDate('date', '>=', Carbon::parse($data['startDate']))
                            )
                            ->when(
                                isset($data['endDate']) && $data['endDate'],
                                fn (Builder $query) => $query->whereDate('date', '<=', Carbon::parse($data['endDate']))
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (isset($data['startDate']) && $data['startDate'] && isset($data['endDate']) && $data['endDate']) {
                            return 'Rentang Tanggal: ' . Carbon::parse($data['startDate'])->toFormattedDateString() . ' - ' . Carbon::parse($data['endDate'])->toFormattedDateString();
                        } elseif (isset($data['startDate']) && $data['startDate']) {
                            return 'Mulai dari: ' . Carbon::parse($data['startDate'])->toFormattedDateString();
                        } elseif (isset($data['endDate']) && $data['endDate']) {
                            return 'Hingga: ' . Carbon::parse($data['endDate'])->toFormattedDateString();
                        }
                        return null;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()
                    ->label('Export Attendance Data')
                    ->exports([
                        ExcelExport::make()
                        ->withColumns([
                            Column::make('date')
                                ->width(10)
                                ->heading('Tanggal'),
                            Column::make('user.name')
                                ->width(10)
                                ->heading('Nama'),
                            Column::make('user.group.name')
                                ->width(10)
                                ->heading('Kelas'),
                            Column::make('check_in_coordinates')
                                ->width(10)
                                ->heading('Koordinat Masuk'),
                            Column::make('check_in_time')
                                ->width(10)
                                ->heading('Waktu Masuk'),
                            Column::make('status')
                                ->width(10)
                                ->heading('Status'),
                            Column::make('check_out_coordinates')
                                ->width(10)
                                ->heading('Koordinat Pulang'),
                            Column::make('check_out_time')
                                ->width(10)
                                ->heading('Waktu Pulang'),
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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'view' => Pages\ViewAttendance::route('/{record}'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
