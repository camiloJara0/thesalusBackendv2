<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Info_equipo extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial',
        'tipo_equipo_id',
        'inventario_id',
    ];
}
