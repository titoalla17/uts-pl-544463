<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Film extends Model
{
    protected $fillable = [
        'judul', 'sutradara', 'tahun_rilis', 'genre', 'rating', 'tanggal_ditonton', 'ulasan', 'favorit',
    ];

    protected function casts(): array
    {
        return [
            'tahun_rilis' => 'integer',
            'rating' => 'float',
            'tanggal_ditonton' => 'date:Y-m-d',
            'favorit' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
