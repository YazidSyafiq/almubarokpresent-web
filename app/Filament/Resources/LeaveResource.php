<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
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

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationGroup = 'Attendace Management';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationIcon = 'heroicon-s-paper-airplane';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Form')
                    ->collapsible()
                    ->columns(2)
                    ->disabled(fn () => !Auth::user()->hasRole('Super Admin'))
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Nama')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Alasan')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Approval Form')
                    ->collapsible()
                    ->hidden(fn (string $context): bool => $context === 'create')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'Pending' => 'Pending',
                                'Approved' => 'Approved',
                                'Rejected' => 'Rejected',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                if ($user->hasRole('Super Admin')) {
                    return;
                }

                if ($user->hasRole('Ustad')) {
                    $query->whereHas('user', function ($subQuery) use ($user) {
                        $subQuery->where('group_id', $user->group_id);
                    });
                } else {
                    $query->where('user_id', $user->id);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->label('Tanggal'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.group.name')
                    ->label('Kelas')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Rejected' => 'danger',
                        'Pending'=> 'warning',
                    }),
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
                Tables\Actions\EditAction::make()
                    ->label('Approval')
                    ->icon('heroicon-c-cursor-arrow-ripple'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()
                    ->label('Export Leave Data')
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
                            Column::make('reason')
                                ->width(10)
                                ->heading('Alasan'),
                            Column::make('status')
                                ->width(10)
                                ->heading('Status'),
                            Column::make('note')
                                ->width(10)
                                ->heading('Catatan'),
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'view' => Pages\ViewLeave::route('/{record}'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}
