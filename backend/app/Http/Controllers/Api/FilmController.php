<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilmController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $films = Film::where('user_id', $request->user()->id)
            ->orderByDesc('tanggal_ditonton')->latest()->get();
        return response()->json(['data' => $films]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $film = $request->user()->films()->create($data);
        return response()->json(['message' => 'Film dicatat.', 'data' => $film->fresh()], 201);
    }

    public function show(Request $request, Film $film): JsonResponse
    {
        $this->ensureOwner($request, $film);
        return response()->json(['data' => $film]);
    }

    public function update(Request $request, Film $film): JsonResponse
    {
        $this->ensureOwner($request, $film);
        $film->update($this->validated($request));
        return response()->json(['message' => 'Film diperbarui.', 'data' => $film->fresh()]);
    }

    public function destroy(Request $request, Film $film): JsonResponse
    {
        $this->ensureOwner($request, $film);
        $film->delete();
        return response()->json(['message' => 'Film dihapus.']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'sutradara' => ['required', 'string', 'max:100'],
            'tahun_rilis' => ['required', 'integer', 'between:1888,' . (now()->year + 5)],
            'genre' => ['required', 'string', 'max:60'],
            // Rating bintang ala Letterboxd: 0.5 sampai 5 dengan kelipatan setengah.
            'rating' => ['nullable', 'numeric', 'between:0.5,5', 'multiple_of:0.5'],
            'tanggal_ditonton' => ['nullable', 'date', 'before_or_equal:today'],
            'ulasan' => ['nullable', 'string', 'max:2000'],
            'favorit' => ['sometimes', 'boolean'],
        ]);
        $data['favorit'] = $data['favorit'] ?? false;
        return $data;
    }

    private function ensureOwner(Request $request, Film $film): void
    {
        abort_unless((int) $film->user_id === (int) $request->user()->id, 404);
    }
}
