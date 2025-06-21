<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Login
Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);
    Route::get('/profile', [App\Http\Controllers\API\ProfileController::class, 'getProfile']);
    Route::post('/profile-update', [App\Http\Controllers\API\ProfileController::class, 'updateProfile']);
    Route::post('/face', [App\Http\Controllers\API\AttendanceController::class, 'postFaceEmbedding']);
    Route::get('/attendance/setup', [App\Http\Controllers\API\AttendanceController::class, 'getSetupPresensi']);
    Route::get('/attendance/today', [App\Http\Controllers\API\AttendanceController::class, 'getAttendanceToday']);
    Route::get('/attendance/month', [App\Http\Controllers\API\AttendanceController::class, 'getAttendanceCurrentMonth']);
    Route::post('/attendance/year', [App\Http\Controllers\API\AttendanceController::class, 'getAttendanceYear']);
    Route::post('/attendance/presensi', [App\Http\Controllers\API\AttendanceController::class, 'presensi']);
    Route::post('/banned', [App\Http\Controllers\API\AttendanceController::class, 'banned']);
    Route::get('/leave/get', [App\Http\Controllers\API\LeaveController::class, 'getIzin']);
    Route::get('/leave/getDetail/{id}', [App\Http\Controllers\API\LeaveController::class, 'getIzinDetail']);
    Route::post('/leave/create', [App\Http\Controllers\API\LeaveController::class, 'createIzin']);
    Route::post('/change-password', [App\Http\Controllers\API\AuthController::class, 'changePassword']);
    Route::post('/fcm-token-register', [App\Http\Controllers\API\AuthController::class, 'registerFcmToken']);
});
