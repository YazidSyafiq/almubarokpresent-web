<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCheckInCoordinatesAttribute()
    {
        return "{$this->check_in_latitude}, {$this->check_in_longitude}";
    }

    public function getCheckOutCoordinatesAttribute()
    {
        return "{$this->check_out_latitude}, {$this->check_out_longitude}";
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendance) {
            $attendance->schedule_latitude = $attendance->user->group->location->latitude;
            $attendance->schedule_longitude = $attendance->user->group->location->longitude;
            $attendance->schedule_check_in = $attendance->user->group->start_time;
            $attendance->schedule_check_out = $attendance->user->group->end_time;
        });

        static::saving(function ($attendance) {
            // Pastikan relasi user dan group tersedia
            if ($attendance->check_in_time && $attendance->user && $attendance->user->group) {
                // Tentukan tanggal dari field 'date' untuk digunakan pada waktu jadwal check-in
                $date = $attendance->date; // Asumsi 'date' menggunakan format 'Y-m-d'

                // Tambahkan tanggal pada schedule_check_in untuk perbandingan yang benar
                $scheduleCheckInTime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $attendance->user->group->start_time);
                $checkInTime = Carbon::createFromFormat('Y-m-d H:i:s', $date . ' ' . $attendance->check_in_time);

                // Perbandingan waktu presensi dan jadwal
                $isLate = $checkInTime->gt($scheduleCheckInTime);

                // Perbarui status secara otomatis
                $attendance->status = $isLate ? 'Terlambat' : 'Tepat Waktu';
            } else {
                // Tangani jika relasi null atau data tidak lengkap
                $attendance->status = 'Data Tidak Lengkap';
            }
        });


    }

}
