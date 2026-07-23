<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    use HasFactory;

    protected $fillable = [
        'direccion',
        'fecha_nota',
        'hora_nota',
        'tipoAnalisis',
        'id_analisis',
    ];

    public function procedimiento(){
        return $this->belongsTo(Plan_manejo_procedimiento::class, 'id_procedimiento');
    }

    public function descripcionNota(){
        return $this->hasMany(Descripcion_nota::class, 'id_nota');
    }
}
