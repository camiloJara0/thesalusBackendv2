<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Info_insumo extends Model
{
    use HasFactory;

    protected $fillable = [
        'unidad',
        'especificaciones',
        'lote',
        'vencimiento',
        'ubicacion',
        'inventario_id',
    ];
}
