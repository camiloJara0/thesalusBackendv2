<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'categoria',
        'stock',
        'estado',
        'es_prestable'
    ];

    public function movimientos(){
        return $this->hasMany(Movimiento::class, 'id_insumo');
    }

    public function infoMedicamento(){
        return $this->hasOne(Info_medicamento::class, 'inventario_id');
    }

    public function infoInsumo(){
        return $this->hasOne(Info_insumo::class, 'inventario_id');
    }

    public function infoEquipo(){
        return $this->hasOne(Info_equipo::class, 'inventario_id');
    }
}
