<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    use HasFactory;

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'cantidadMovimiento',
        'tipoMovimiento',
        'fechaMovimiento',
        'id_medico',
        'id_insumo',
        'id_analisis',
        'id_paciente',
    ];

    public function insumo(){
        return $this->belongsTo(Insumo::class, 'id_insumo');
    }

    public function medico(){
        return $this->belongsTo(Profesional::class, 'id_medico');
    }

    public function analisis(){
        return $this->belongsTo(Analisis::class, 'id_analisis');
    }

    public function paciente(){
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function historialInsumoprestado(){
        return $this->hasMany(Historial_insumoprestado::class, 'id_movimiento');
    }

}
