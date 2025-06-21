<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leave;
use App\Models\Attendance;

class LeaveController extends Controller
{
    public function getIzin()
    {
        $user = auth()->user();
        $izin = Leave::where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $izin,
            'message' => 'Data izin berhasil diambil'
        ], 200);
    }

    public function getIzinDetail($id)
    {
        $user = auth()->user();
        $izin = Leave::where('user_id', $user->id)
            ->where('id', $id)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $izin,
            'message' => 'Data izin berhasil diambil'
        ], 200);
    }

    public function createIzin(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'date' => 'required|date',
            'reason' => 'required|string',
        ]);

        if ($user->is_banned) {
            return response()->json([
                'success' => false,
                'message' => 'You are banned',
            ], 422);
        }

        $today = now()->toDateString();

        $attendanceToday = Attendance::where('user_id', $user->id)
                            ->whereDate('date', $today)
                            ->exists();

        $leaveApproved = Leave::where('user_id', $user->id)
                            ->whereDate('date', $today)
                            ->where('status', 'Approved')
                            ->exists();

        if ($leaveApproved) {
            return response()->json([
                'success' => false,
                'message' => 'You have a leave approved today',
            ], 422);
        }

        if ($attendanceToday) {
            return response()->json([
                'success' => false,
                'message' => 'You have already attended today',
            ], 422);
        }

        $izin = Leave::create([
            'user_id' => $user->id,
            'date' => $request->date,
            'reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'data' => $izin,
            'message' => 'Izin berhasil dibuat'
        ], 200);
    }
}
