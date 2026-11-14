<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Film;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

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

        // LOG TERSTRUKTUR 1 (INFO): Berhasil menyimpan data
        Log::info('Catatan film baru berhasil disimpan', [
            'actor' => 'user_id:' . $request->user()->id,
            'action' => 'menyimpan_data_film',
            'reason' => 'Seluruh aturan validasi terpenuhi dan data berhasil ditulis ke basis data',
            'film_id' => $film->id,
        ]);

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
        try {
            $data = $request->validate([
                'judul' => ['required', 'string', 'max:150'],
                'sutradara' => ['required', 'string', 'max:100'],
                'tahun_rilis' => ['required', 'integer', 'between:1888,' . (now()->year + 5)],
                'genre' => ['required', 'string', 'max:60'],
                'rating' => ['nullable', 'numeric', 'between:0.5,5', 'multiple_of:0.5'],
                'tanggal_ditonton' => ['nullable', 'date', 'before_or_equal:today'],
                'ulasan' => ['nullable', 'string', 'max:2000'],
                'favorit' => ['sometimes', 'boolean'],
            ]);
        } catch (ValidationException $e) {
            // LOG TERSTRUKTUR 2 (WARNING): Validasi gagal
            Log::warning('Validasi input data film gagal', [
                'actor' => 'user_id:' . ($request->user()?->id ?? 'guest'),
                'action' => 'validasi_form_film',
                'reason' => 'Payload tidak sesuai aturan skema: ' . implode(', ', array_keys($e->errors())),
            ]);
            throw $e;
        }

        $data['favorit'] = $data['favorit'] ?? false;
        return $data;
    }

    private function ensureOwner(Request $request, Film $film): void
    {
        if ((int) $film->user_id !== (int) $request->user()->id) {
            // LOG TERSTRUKTUR 3 (WARNING): Akses ditolak karena bukan pemilik data
            Log::warning('Percobaan akses film milik pengguna lain ditolak', [
                'actor' => 'user_id:' . $request->user()->id,
                'action' => 'otorisasi_kepemilikan_film',
                'reason' => 'ID pemilik film tidak cocok dengan pengguna yang sedang terautentikasi',
                'target_film_id' => $film->id,
            ]);
            abort(404);
        }
    }
}