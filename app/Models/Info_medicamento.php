<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Info_medicamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'activo',
        'unidad',
        'lote',
        'vencimiento',
        'inventario_id',
    ];
}
