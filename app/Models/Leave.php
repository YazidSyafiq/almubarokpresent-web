<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'reason',
        'status',
        'note',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function sendFirebaseNotification($title, $body, $userId)
    {
        try {
            $messaging = app('firebase.messaging');

            // Get user's FCM token from your database
            $user = User::find($userId);
            $deviceToken = $user->fcm_token; // Assuming you store FCM token in fcm_token column

            if (!$deviceToken) {
                return;
            }

            $notification = Notification::create($title, $body);

            $message = CloudMessage::withTarget('token', $deviceToken)
                ->withNotification($notification)
                ->withData([
                    'id' => (string)$this->id,
                    'type' => 'leave',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ]);

            $messaging->send($message);
        } catch (\Exception $e) {
            \Log::error('Firebase Notification Error: ' . $e->getMessage());
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::updated(function ($request) {
            if ($request->isDirty('status')) {
                if($request->status === 'Approved'){
                    $request->sendFirebaseNotification('Izin', 'Pengajuan Izin Anda Telah Diterima', $request->user_id);
                }
                elseif($request->status === 'Rejected') {
                    $request->sendFirebaseNotification('Izin', 'Pengajuan Izin Anda Telah Ditolak', $request->user_id);
                }
            }
        });
    }
}
