<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profesional_has_permisos extends Model
{
    protected $table = 'profesional_has_permisos';
    use HasFactory;

    protected $fillable = [
        'id_profesional',
        'id_seccion',
        'fecha_inicio',
        'fecha_fin',
        'usado',
        'codigo',
    ];

    public function profesional(){
        return $this->belongsTo(Profesional::class, 'id_profesional');
    }
    public function seccion(){
        return $this->belongsTo(Secciones::class, 'id_seccion');
    }
}
