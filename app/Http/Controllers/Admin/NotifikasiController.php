<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifikasi = $user->notifications()->latest()->take(15)->get()->map(function (Model $notifikasi) {
            return [
                'id' => $notifikasi->id,
                'judul' => $notifikasi->data['judul'] ?? '',
                'pesan' => $notifikasi->data['pesan'] ?? '',
                'url' => $notifikasi->data['url'] ?? null,
                'dibaca' => $notifikasi->read_at !== null,
                'waktu' => $notifikasi->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'belum_dibaca' => $user->unreadNotifications()->count(),
            'notifikasi' => $notifikasi,
        ]);
    }

    public function tandaiDibaca(Request $request, string $notifikasi): JsonResponse
    {
        $item = $request->user()->notifications()->where('id', $notifikasi)->firstOrFail();
        $item->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    public function tandaiSemuaDibaca(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['status' => 'ok']);
    }
}
