<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_paciente',
        'id_medico',
        'motivo',
        'fecha',
        'hora',
        'estado',
        'motivo_cancelacion',
        'id_procedimiento',
        'fechaHasta',
        'motivo_edicion',
        'id_analisis',
        'id_servicio',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }
    public function profesional()
    {
        return $this->belongsTo(Profesional::class, 'id_medico');
    }
    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'id_servicio');
    }
    public function analisis()
    {
        return $this->belongsTo(Analisis::class, 'id_analisis');
    }
}
