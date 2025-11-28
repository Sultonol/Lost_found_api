<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index(Claim $claim)
    {
        $user = Auth::user();
        if ($user->id !== $claim->claimer_id && $user->id !== $claim->finder_id) {
            return response()->json(['message' => 'Anda tidak diizinkan mengakses chat ini.'], 403);
        }

        $messages = $claim->messages()
                         ->with('sender:id,name')
                         ->orderBy('created_at', 'asc')
                         ->get();

        return response()->json(['data' => $messages]);
    }


    public function store(Request $request, Claim $claim)
    {
        $user = Auth::user();

        if ($user->id !== $claim->claimer_id && $user->id !== $claim->finder_id) {
            return response()->json(['message' => 'Anda tidak diizinkan mengirim pesan di chat ini.'], 403);
        }
        if ($claim->status->value === 'rejected') {
            return response()->json(['message' => 'Tidak bisa mengirim pesan, klaim ditolak.'], 400);
        }
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);
        $receiverId = ($user->id == $claim->claimer_id) ? $claim->finder_id : $claim->claimer_id;

        $message = $claim->messages()->create([
            'sender_id' => $user->id,
            'receiver_id' => $receiverId,
            'message' => $validated['message'],
        ]);

        return response()->json($message->load('sender:id,name'), 201);
    }
}
