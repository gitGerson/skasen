<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aspirasi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tujuan_id',
        'kategori_id',
        'keterangan',
        'image_path',
        'is_anonymous',
        'status',
    ];

    protected $casts = [
        'is_anonymous' => 'boolean',
    ];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Tujuan
    public function tujuan()
    {
        return $this->belongsTo(Tujuan::class);
    }

    // Relasi ke Kategori
    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}
