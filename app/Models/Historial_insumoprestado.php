<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Historial_insumoprestado extends Model
{
    use HasFactory;
    protected $table = 'historial_insumoprestados';
    protected $fillable = [
        'id_insumo',
        'id_movimiento',
        'fecha_desde',
        'fecha_hasta',
        'observacion',
        'estado',
    ];

    public function movimiento()
    {
        return $this->belongsTo(Movimiento::class, 'id_movimiento');
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'id_insumo');
    }
}
