<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profesion_has_permisos extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_profesion',
        'id_seccion',
    ];

    public function profesion(){
        return $this->belongsTo(Profesion::class, 'id_profesion');
    }
    public function seccion(){
        return $this->belongsTo(Secciones::class, 'id_seccion');
    }
}
