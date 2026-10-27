<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Memastikan auth guard tersinkronisasi jika menggunakan demo switcher.
     */
    protected function syncAuthUser(): ?User
    {
        if (! auth()->check() && session()->has('demo_role')) {
            $demoUser = User::where('role', session('demo_role'))->first();
            if ($demoUser) {
                auth()->login($demoUser);
            }
        }

        return auth()->user();
    }

    /**
     * Menampilkan daftar notifikasi pengguna yang terautentikasi.
     */
    public function index(Request $request)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return redirect()->route('login');
        }

        $filter = $request->query('filter', 'all');

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query = $user->unreadNotifications();
        }

        $notifications = $query->latest()->paginate(15)->withQueryString();
        $unreadCount = $user->unreadNotifications()->count();

        return view('notifications.index', compact('notifications', 'unreadCount', 'filter'));
    }

    /**
     * Endpoint ringan untuk auto-polling jumlah notifikasi belum dibaca di navbar.
     */
    public function unreadCount(Request $request)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            return response()->json(['unread_count' => 0]);
        }

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Menandai satu notifikasi sebagai sudah dibaca.
     * 
     * MITIGASI IDOR (Modul Minggu 10 Poin 5 & Checkpoint 10.4):
     * Query scoping dilakukan melalui $user->notifications()->findOrFail($id)
     * sehingga pengguna dilarang dan tidak dapat menandai notifikasi milik orang lain.
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            abort(401);
        }

        // Query scoping: Hanya mencari notifikasi yang berelasi dengan pengguna saat ini
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi berhasil ditandai sebagai sudah dibaca.',
            ]);
        }

        return back()->with('success', 'Notifikasi berhasil ditandai sebagai sudah dibaca.');
    }

    /**
     * Menandai seluruh notifikasi pengguna sebagai sudah dibaca.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $this->syncAuthUser();
        if (! $user) {
            abort(401);
        }

        $user->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Semua notifikasi berhasil ditandai sebagai sudah dibaca.',
            ]);
        }

        return back()->with('success', 'Semua notifikasi berhasil ditandai sebagai sudah dibaca.');
    }
}
