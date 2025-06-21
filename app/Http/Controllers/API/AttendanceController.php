<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Validator;
use App\Models\Group;
use App\Models\Leave;
use App\Http\Resources\AttendanceResource;

class AttendanceController extends Controller
{
    public function getAttendanceToday()
    {
        $userId = auth()->user()->id;
        $today = now()->toDateString();

        $user = auth()->user();

        $attendanceToday = Attendance::select('check_in_time', 'check_out_time', 'status')
            ->where('user_id', $userId)
            ->whereDate('date', $today)
            ->first();

        $permissionCheckIn = false;
        if ($attendanceToday) {
            if (is_null($attendanceToday->check_in_time)) {
                $permissionCheckIn = true;
            }
        } else {
            // Jika belum ada data attendance untuk hari ini, bisa disesuaikan apakah true atau false
            $permissionCheckIn = true;
        }

        $permissionCheckOut = false;
        if ($attendanceToday) {
            if (!is_null($attendanceToday->check_in_time) && is_null($attendanceToday->check_out_time)) {
                $permissionCheckOut = true;
            }
        } else {
            // Jika belum ada data attendance untuk hari ini, bisa disesuaikan apakah true atau false
            $permissionCheckOut = false;
        }

        return response()->json([
                'success' => true,
                'data' => [
                    'check_in_time' => $attendanceToday ? $attendanceToday->check_in_time : null,
                    'check_out_time' => $attendanceToday ? $attendanceToday->check_out_time : null,
                    'status' => $attendanceToday ? $attendanceToday->status : null,
                    'is_banned' => $user->is_banned,
                    'permission_check_in' => $permissionCheckIn,
                    'permission_check_out' => $permissionCheckOut,
                ],
                'message' => 'Success get attendance today',
            ], 200);
    }

    public function getAttendanceCurrentMonth()
    {
        $userId = auth()->user()->id;
        $currentMonth = now()->month;

        $attendanceMonth = Attendance::select('date', 'check_in_time', 'check_out_time', 'status')
            ->where('user_id', $userId)
            ->whereMonth('date', $currentMonth)
            ->get()
            ->map(function ($attendance) {
                return [
                    'date' => $attendance->date,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'status' => $attendance->status,
                ];
            });

        return response()->json([
                'success' => true,
                'data' => $attendanceMonth,
                'message' => 'Success get attendance month',
            ], 200);
    }

    public function getAttendanceYear(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $userId = auth()->user()->id;
        $currentYear = now()->year;

        $attendanceYear = Attendance::select('date', 'check_in_time', 'check_out_time', 'status')
            ->where('user_id', $userId)
            ->whereMonth('date', $request->month)
            ->whereYear('date', $currentYear)
            ->get()
            ->map(function ($attendance) {
                return [
                    'date' => $attendance->date,
                    'check_in_time' => $attendance->check_in_time,
                    'check_out_time' => $attendance->check_out_time,
                    'status' => $attendance->status,
                ];
            });

        return response()->json([
                'success' => true,
                'data' => $attendanceYear,
                'message' => 'Success get attendance year',
            ], 200);
    }

    public function getSetupPresensi()
    {
        $today = now()->toDateString();

        $user = auth()->user();

        $leaveApproved = Leave::where('user_id', $user->id)
        ->whereDate('date', $today)
        ->where('status', 'Approved')
        ->exists();

        return response()->json([
                'success' => true,
                'data' => [
                    'latitude' => $user->group->location->latitude,
                    'longitude' => $user->group->location->longitude,
                    'radius' => $user->group->location->radius_m,
                    'face_embedding' => $user->face_embedding,
                    'is_banned' => $user->is_banned,
                    'leave_approved' => $leaveApproved

                ],
                'message' => 'Success get setup presensi',
            ], 200);
    }

    public function postFaceEmbedding(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'face_embedding' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $user->update([
            'face_embedding' => $request->face_embedding,
        ]);

        return response()->json([
                'success' => true,
                'message' => 'Success store face embedding',
            ], 200);
    }

    public function presensi(Request $request)
    {
        $validator = Validator::make($request->all(), [
             'latitude' => 'required|numeric',
             'longitude' => 'required|numeric',
         ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $groupId = auth()->user()->group_id;
        $user = auth()->user();
        $schedule = Group::where('id', $groupId)->first();
        $today = now()->toDateString();

        $leaveApproved = Leave::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->where('status', 'Approved')
            ->exists();

        if ($user->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'You are banned',
            ], 422);
        }

        if ($leaveApproved) {
            return response()->json([
                'success' => false,
                'message' => 'You have a leave approved today',
            ], 422);
        }

        if ($schedule) {
            $userId = auth()->user()->id;
            $attendance = Attendance::where('user_id', $userId)
                            ->whereDate('date', now()->toDateString())
                            ->first();

            if (!$attendance) {
                $attendance = Attendance::create([
                    'user_id' => $userId,
                    'schedule_latitude' => $schedule->latitude,
                    'schedule_longitude' => $schedule->longitude,
                    'schedule_check_in' => $schedule->check_in,
                    'schedule_check_out' => $schedule->check_out,
                    'check_in_latitude' => $request->latitude,
                    'check_in_longitude' => $request->longitude,
                    'date' => now()->toDateString(),
                    'check_in_time' => now()->toTimeString(),
                ]);
            } else {
                $attendance->update([
                    'check_out_latitude' => $request->latitude,
                    'check_out_longitude' => $request->longitude,
                    'check_out_time' => now()->toTimeString(),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => new AttendanceResource($attendance),
                'message' => 'Success store attendance',
            ], 200);

        } else {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
            ], 404);
        }
    }

    public function banned()
    {
        $user = auth()->user();
        $user->update([
            'is_banned' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Success banned user',
        ], 200);
    }
}
