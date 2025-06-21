<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceChart extends ChartWidget
{
    protected static ?string $heading = 'Weekly Attendance';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        // Cek apakah user adalah Super Admin
        $user = auth()->user();

        if ($user->hasRole('Super Admin') || $user->hasRole('Ustad')) {
            // Jika Super Admin, ambil laporan untuk seluruh pengguna
            $onTimeAttendance = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
                ->where('status', 'Tepat Waktu') // Asumsi kolom status untuk menentukan on time
                ->selectRaw('DATE(date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            $lateAttendance = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
                ->where('status', 'Terlambat') // Asumsi kolom status untuk menentukan late
                ->selectRaw('DATE(date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');
        } else {
            // Jika bukan Super Admin, ambil laporan untuk user tersebut
            $onTimeAttendance = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
                ->where('user_id', $user->id)
                ->where('status', 'Tepat Waktu')
                ->selectRaw('DATE(date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            $lateAttendance = Attendance::whereBetween('date', [$startOfWeek, $endOfWeek])
                ->where('user_id', $user->id)
                ->where('status', 'Terlambat')
                ->selectRaw('DATE(date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');
        }

        // Isi data hari yang tidak memiliki laporan dengan nilai 0
        $onTimeData = [];
        $lateData = [];
        $daysOfWeek = [];

        foreach (range(0, 6) as $day) {
            $date = $startOfWeek->copy()->addDays($day);
            $formattedDate = $date->format('Y-m-d');

            $onTimeData[] = $onTimeAttendance->get($formattedDate, 0);
            $lateData[] = $lateAttendance->get($formattedDate, 0);
            $daysOfWeek[] = $date->format('D'); // Format singkatan hari (Mon, Tue, etc.)
        }

        return [
            'datasets' => [
                [
                    'label' => 'Tepat Waktu',
                    'data' => $onTimeData,
                    'backgroundColor' => 'rgb(34, 197, 94)', // Hijau transparan
                    'borderColor' => 'rgb(34, 197, 94)', // Hijau solid
                    'borderWidth' => 2,
                    'fill' => false,
                    'pointBackgroundColor' => 'rgb(34, 197, 94)', // Warna titik hijau
                    'pointBorderColor' => 'rgb(34, 197, 94)', // Border titik hijau
                    'pointRadius' => 2, // Ukuran titik
                    'pointHoverRadius' => 5, // Ukuran titik saat hover
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $lateData,
                    'backgroundColor' => 'rgb(239, 68, 68)', // Merah transparan
                    'borderColor' => 'rgb(239, 68, 68)', // Merah solid
                    'borderWidth' => 2,
                    'fill' => false,
                    'pointBackgroundColor' => 'rgb(239, 68, 68)', // Warna titik merah
                    'pointBorderColor' => 'rgb(239, 68, 68)', // Border titik merah
                    'pointRadius' => 2, // Ukuran titik
                    'pointHoverRadius' => 5, // Ukuran titik saat hover
                ],
            ],
            'labels' => $daysOfWeek,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): ?array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true, // Sumbu Y mulai dari 0
                    'suggestedMin' => 0, // Minimal sumbu Y adalah 0
                    'suggestedMax' => 6, // Maksimal awal sumbu Y adalah 6
                ],
            ],
        ];
    }
}
