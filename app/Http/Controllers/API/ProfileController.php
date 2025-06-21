<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\ProfileResource;

class ProfileController extends Controller
{
    public function getProfile()
    {
        $user = auth()->user();

        return response()->json([
            'success' => true,
            'data' => new ProfileResource($user),
            'message' => 'Profile fetch successful'
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $oldPhoto = $user->image_url;

        if ($request->hasFile('image')) {
            $request->validate([
                'image' => 'required|image|max:2048',
            ]);

            $path = $request->file('image')->store('', 'public');
            $user->image_url = $path;
        }

        $user->name = $request->name ? $request->name : $user->name ;
        $user->email = $request->email ? $request->email : $user->email;
        $user->phone = $request->phone ? $request->phone : $user->phone;

        if ($user->save()) {
            if ($oldPhoto !== $user->image_url && $oldPhoto !== null) {
                Storage::disk('public')->delete($oldPhoto);
            }

            return response()->json([
                'success' => true,
                'data' => new ProfileResource($user),
                'message' => 'Profile update successful'
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed',
            ], 422);
        }
    }
}
