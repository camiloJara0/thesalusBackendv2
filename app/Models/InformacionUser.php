<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InformacionUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'No_document',
        'type_doc',
        'name',
        'nacimiento',
        'sexo',
        'celular',
        'telefono',
        'direccion',
        'municipio',
        'departamento',
        'barrio',
        'zona',
        'estado'
    ];
}
