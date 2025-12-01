<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Mengambil daftar pesan (Chat History).
     */
    public function index(Claim $claim)
    {
        $user = Auth::user();

        // 1. Validasi: Hanya Pelapor & Penemu yang boleh lihat chat ini
        if ($user->id !== $claim->claimer_id && $user->id !== $claim->finder_id) {
            return response()->json(['message' => 'Anda tidak diizinkan mengakses chat ini.'], 403);
        }

        // 2. Ambil Pesan
        $messages = $claim->messages()
                         ->with('sender')
                         ->orderBy('created_at', 'asc')
                         ->get();

        // 3. Return dengan format 'data'
        return response()->json(['data' => $messages]);
    }

    /**
     * Mengirim pesan baru.
     */
    public function store(Request $request, Claim $claim)
    {
        $user = Auth::user();

        // 1. Validasi: Hanya Pelapor & Penemu yang boleh kirim pesan
        if ($user->id !== $claim->claimer_id && $user->id !== $claim->finder_id) {
            return response()->json(['message' => 'Anda tidak diizinkan mengirim pesan di chat ini.'], 403);
        }

        // 2. Cek Status Klaim
        if ($claim->status->value === 'rejected') {
            return response()->json(['message' => 'Tidak bisa mengirim pesan, klaim ditolak.'], 400);
        }

        // 3. Validasi Input
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // 4. Tentukan Penerima Pesan
        $receiverId = ($user->id == $claim->claimer_id) ? $claim->finder_id : $claim->claimer_id;

        // 5. Simpan Pesan
        $message = $claim->messages()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'message' => $validated['message'],
        ]);

        // 6. Return response sukses
        // PERBAIKAN DI SINI JUGA: Gunakan 'sender' full
        return response()->json($message->load('sender'), 201);
    }
}
