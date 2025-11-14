<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Events\Verified;

class ClaimController extends Controller
{
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

        if ($item->status == 'claimed') {
            $activeClaim = Claim::where('item_id', $item->id)->where('status', 'pending')->first();
            $claimerName = $activeClaim ? $activeClaim->claimer->name : 'pengguna lain';
            return response()->json(['message' => "Barang ini sedang dalam proses klaim oleh {$claimerName}."], 409);
        } elseif ($item->status == 'closed') {
            return response()->json(['message' => 'Laporan barang ini sudah ditutup.'], 409);
        } elseif ($item->status->value != 'open') {
             return response()->json(['message' => 'Barang ini tidak bisa diklaim saat ini.'], 409);
        }

        $claim = Claim::create([
            'item_id' => $item->id,
            'claimer_id' => $user->id,
            'finder_id' => $item->user_id,
            'status' => 'pending',
        ]);

        $item->update(['status' => 'claimed']);

        return response()->json([
            'message' => 'Klaim berhasil dibuat. Penemu barang akan segera dihubungi.',
            'claim' => $claim->load(['item', 'claimer', 'finder'])
        ], 201);
    }

    public function show(Claim $claim)
    {
         if (auth()->id() !== $claim->claimer_id && auth()->id() !== $claim->finder_id) {
             return response()->json(['message' => 'Tidak diizinkan.'], 403);
         }
         return $claim->load(['item.user', 'item.category', 'claimer', 'finder']);
    }

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

    public function destroy(Claim $claim)
    {
        return response()->json(null, 404);
    }
}

