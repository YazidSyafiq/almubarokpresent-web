<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Leave;

class LeaveChart extends ChartWidget
{
     protected static ?string $heading = 'Monthly Leaves';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $user = auth()->user();

        // Cek apakah user adalah Super Admin
        if ($user->hasRole('Super Admin') || $user->hasRole('Ustad')) {
            // Jika Super Admin, ambil laporan seluruh tahun ini
            $monthlyData = Leave::query()
                ->selectRaw('MONTH(date) as month, COUNT(*) as count')
                ->whereYear('date', now()->year) // Hanya data dari tahun ini
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray();
        } else {
            // Jika bukan Super Admin, ambil laporan per bulan untuk user tersebut
            $monthlyData = Leave::query()
                ->selectRaw('MONTH(date) as month, COUNT(*) as count')
                ->whereYear('date', now()->year) // Hanya data dari tahun ini
                ->where('user_id', $user->id) // Asumsi ada kolom 'user_id' di tabel laporan
                ->groupBy('month')
                ->pluck('count', 'month')
                ->toArray();
        }

        // Siapkan data untuk setiap bulan
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $data[] = $monthlyData[$month] ?? 0; // Jika tidak ada data, gunakan 0
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leaves',
                    'data' => $data,
                    'backgroundColor' => 'rgb(212, 175, 55)', // Primary color dengan transparansi
                    'borderWidth' => 2,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Menggunakan grafik batang
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'min' => 0, // Sumbu Y dimulai dari 0
                    'suggestedMax' => 6, // Default sumbu Y diatur ke 6 jika datanya kecil
                ],
            ],
        ];
    }
}
