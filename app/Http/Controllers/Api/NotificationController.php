<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    /**
     * Tampilkan notifikasi pengguna yang sedang login.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = \App\Models\Notification::where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $request->user()->id)
            ->latest('created_at')
            ->paginate(15);

        return NotificationResource::collection($notifications);
    }

    /**
     * Tandai notifikasi sebagai sudah dibaca.
     */
    public function read(Request $request, string $id): NotificationResource
    {
        $notification = \App\Models\Notification::where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['read_at' => now()]);

        return new NotificationResource($notification);
    }
}
