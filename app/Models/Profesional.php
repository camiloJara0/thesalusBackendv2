<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profesional extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_profesion',
        'id_infoUsuario',
        'zona_laboral',
        'departamento_laboral',
        'municipio_laboral',
        'estado',
        'sello',
    ];

    public function infoUsuario(){
        return $this->belongsTo(InformacionUser::class, 'id_infoUsuario');
    }
    public function profesion(){
        return $this->belongsTo(Profesion::class, 'id_profesion');
    }
    public function user(){
        return $this->belongsTo(User::class, 'id_infoUsuario', 'id_infoUsuario');
    }
}
