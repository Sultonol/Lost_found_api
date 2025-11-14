<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['user', 'category'])->latest();

        if ($request->has('report_type')) {
            $query->where('report_type', $request->report_type);
        }

        if ($request->has('keyword')) {
            $query->where('item_name', 'like', '%' . $request->keyword . '%');
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'location' => 'required|string|max:255',
            'report_type' => ['required', Rule::in(['hilang', 'ditemukan'])],
            'report_date' => 'required|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('items', 'public');
        }

        $item = auth()->user()->items()->create([
            'item_name' => $validated['item_name'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'location' => $validated['location'],
            'report_type' => $validated['report_type'],
            'report_date' => $validated['report_date'],
            'image_url' => $path ? asset('storage/' . $path) : null,
        ]);

        return response()->json($item, 201);
    }

    public function show(Item $item)
    {
        return $item->load(['user', 'category']);
    }

    public function update(Request $request, Item $item)
    {
        if (auth()->id() !== $item->user_id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $validated = $request->validate([
            'item_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'location' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($item->image_url) {
                Storage::disk('public')->delete(str_replace(asset('storage/'), '', $item->image_url));
            }
            $path = $request->file('image')->store('items', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        }

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy(Item $item)
    {
        if (auth()->id() !== $item->user_id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        if ($item->image_url) {
            Storage::disk('public')->delete(str_replace(asset('storage/'), '', $item->image_url));
        }

        $item->delete();

        return response()->json(['message' => 'Laporan berhasil dihapus.'], 200);
    }
}

