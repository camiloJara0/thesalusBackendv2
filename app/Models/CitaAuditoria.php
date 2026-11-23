<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CitaAuditoria extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'id_cita',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'id_usuario',
        'usuario',
        'detalle',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function cita()
    {
        return $this->belongsTo(Cita::class, 'id_cita');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
