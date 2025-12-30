<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AspirasiPrioritas extends Model
{
    use HasFactory;

    protected $table = 'aspirasi_prioritas';

    protected $fillable = [
        'aspirasi_id',
        'prioritas',
        'confidence',
        'alasan_singkat',
        'model',
        'vector_store_id',
        'classified_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'classified_at' => 'datetime',
    ];

    public function aspirasi()
    {
        return $this->belongsTo(Aspirasi::class);
    }
}
