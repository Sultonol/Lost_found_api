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
    // 1. GET ALL CLAIMS (My Claims & Incoming) - Optional usage
    public function index(Request $request)
    {
        $user = $request->user();
        $claims = Claim::where('claimer_id', $user->id)
                      ->orWhere('finder_id', $user->id)
                      ->with(['item.user', 'item.category', 'claimer', 'finder'])
                      ->latest()
                      ->paginate(10);

        return response()->json($claims);
    }

    // 2. CREATE CLAIM
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

        // Validasi
        if ($item->user_id == $user->id) {
            return response()->json(['message' => 'Anda tidak bisa mengklaim barang Anda sendiri.'], 403);
        }
        if ($item->report_type->value != 'ditemukan') {
             return response()->json(['message' => 'Hanya laporan barang ditemukan yang bisa diklaim.'], 400);
        }

        // Cek status item
        if ($item->status == 'claimed') {
            $activeClaim = Claim::where('item_id', $item->id)->where('status', 'pending')->first();
            $claimerName = $activeClaim ? $activeClaim->claimer->name : 'pengguna lain';
            return response()->json(['message' => "Barang ini sedang dalam proses klaim oleh {$claimerName}."], 409);
        } elseif ($item->status == 'closed') {
            return response()->json(['message' => 'Laporan barang ini sudah ditutup.'], 409);
        } elseif ($item->status->value != 'open') {
             return response()->json(['message' => 'Barang ini tidak bisa diklaim saat ini.'], 409);
        }

        // Buat Claim
        $claim = Claim::create([
            'item_id' => $item->id,
            'claimer_id' => $user->id,
            'finder_id' => $item->user_id, // Pastikan ini terisi!
            'status' => 'pending',
        ]);

        // Update status barang jadi 'claimed'
        $item->update(['status' => 'claimed']);

        return response()->json([
            'message' => 'Klaim berhasil dibuat. Penemu barang akan segera dihubungi.',
            'claim' => $claim->load(['item', 'claimer', 'finder'])
        ], 201);
    }

    // 3. SHOW DETAIL
    public function show(Claim $claim)
    {
         if (auth()->id() !== $claim->claimer_id && auth()->id() !== $claim->finder_id) {
             return response()->json(['message' => 'Tidak diizinkan.'], 403);
         }
         return $claim->load(['item.user', 'item.category', 'claimer', 'finder']);
    }

    // 4. UPDATE STATUS (Approve / Reject)
    public function update(Request $request, Claim $claim)
    {
        if (auth()->id() !== $claim->finder_id) {
            return response()->json(['message' => 'Hanya penemu barang yang bisa menyetujui/menolak klaim.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        if ($claim->status->value != 'pending') {
            return response()->json(['message' => 'Klaim ini sudah diselesaikan sebelumnya.'], 409);
        }

        $item = $claim->item;
        $message = '';

        if ($validated['status'] == 'approved') {
            $item->update(['status' => 'closed']);
            $message = 'Klaim telah disetujui.';
        } else {
            $item->update(['status' => 'open']);
            $message = 'Klaim telah ditolak.';
        }

        $claim->update(['status' => $validated['status']]);

        return response()->json([
            'message' => $message,
            'claim' => $claim->fresh()->load(['item', 'claimer', 'finder'])
        ]);
    }

    // 5. DELETE
    public function destroy(Claim $claim)
    {
        return response()->json(null, 404);
    }

    // 6. GET INCOMING CLAIMS (Notifikasi untuk Penemu)
    // --- INI BAGIAN YANG TADI ERROR ---
    public function getIncomingClaims(Request $request)
    {
        $user = $request->user();

        // Perhatikan variabel ini namanya $claims (pakai 's' karena jamak/banyak)
        $claims = Claim::where('finder_id', $user->id)
            ->where('status', 'pending')
            ->with(['item', 'claimer'])
            ->latest()
            ->get();

        // YANG BENAR: return ['data' => $claims]
        // KESALAHAN TADI: return ['data' => $claim] <- kurang 's'
        return response()->json(['data' => $claims]);
    }
}
