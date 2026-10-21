<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'servicio',
        'filtros',
        'estado',
        'total',
        'completados',
        'zip_path',
        'zip_name',
    ];

    protected $casts = [
        'filtros' => 'array',
    ];
}
