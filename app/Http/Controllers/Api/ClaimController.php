<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClaimController extends Controller
{
    public function getMySubmittedClaims(Request $request)
    {
        $user = $request->user();

        $claims = Claim::where('claimer_id', $user->id)
                      ->with(['item.user', 'finder'])
                      ->latest()
                      ->get();

        return response()->json(['data' => $claims]);
    }

    public function getIncomingClaims(Request $request)
    {
        $user = $request->user();

        $claims = Claim::where('finder_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->with(['item', 'claimer'])
            ->latest()
            ->get();

        return response()->json(['data' => $claims]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $item = Item::find($request->item_id);
        $user = auth()->user();

        if ($item->user_id == $user->id) {
            return response()->json(['message' => 'Anda tidak bisa mengklaim barang Anda sendiri.'], 403);
        }
        if ($item->report_type->value != 'ditemukan') {
             return response()->json(['message' => 'Hanya laporan barang ditemukan yang bisa diklaim.'], 400);
        }

        // Cek apakah barang sudah closed atau claimed
        if ($item->status == 'claimed') {
            return response()->json(['message' => "Barang ini sedang dalam proses klaim."], 409);
        } elseif ($item->status == 'closed') {
            return response()->json(['message' => 'Laporan barang ini sudah ditutup/selesai.'], 409);
        }

        $claim = Claim::create([
            'item_id' => $item->id,
            'claimer_id' => $user->id,
            'finder_id' => $item->user_id,
            'status' => 'pending',
        ]);

        // Saat klaim dibuat, status barang jadi 'claimed' (agar tidak bisa diklaim orang lain dulu)
        $item->update(['status' => 'claimed']);

        return response()->json([
            'message' => 'Klaim berhasil dibuat.',
            'claim' => $claim->load(['item', 'claimer', 'finder'])
        ], 201);
    }

    public function update(Request $request, Claim $claim)
    {
        if (auth()->id() !== $claim->finder_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        $item = $claim->item;

        if ($validated['status'] == 'approved') {
            // --- PERBAIKAN DI SINI ---
            // Jika klaim DISETUJUI (Approved), ubah status barang jadi 'closed'
            // Ini akan membuat label di frontend berubah menjadi 'Selesai' atau hilang dari list 'Tersedia'
            $item->update(['status' => 'closed']);
        } else {
            // Jika klaim DITOLAK (Rejected), kembalikan status barang jadi 'open'
            // Agar bisa diklaim oleh orang lain lagi
            $item->update(['status' => 'open']);
        }

        $claim->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Status klaim diperbarui',
            'claim' => $claim->fresh()->load(['item', 'claimer', 'finder'])
        ]);
    }

    public function show(Claim $claim)
    {
         if (auth()->id() !== $claim->claimer_id && auth()->id() !== $claim->finder_id) {
             return response()->json(['message' => 'Tidak diizinkan.'], 403);
         }
         return $claim->load(['item.user', 'item.category', 'claimer', 'finder']);
    }

    public function destroy(Claim $claim)
    {
        return response()->json(null, 404);
    }

    public function index(Request $request)
    {
        return $this->getMySubmittedClaims($request);
    }
}
