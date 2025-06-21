<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('Ustad');
    }

    protected function getStats(): array
    {
        $totalUsers = User::count();
        $totalAttendance = Attendance::whereDate('date', Carbon::today())->count();
        $monthlyLeave = Leave::whereMonth('date', Carbon::now()->month)
            ->whereYear('date', Carbon::now()->year)
            ->count();

        return [
            Stat::make('Total users', $totalUsers),
            Stat::make('Attendances today', $totalAttendance),
            Stat::make('Monthly leaves', $monthlyLeave),
        ];
    }
}
