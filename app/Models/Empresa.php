<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'no_identificacion',
        'DV',
        'direccion',
        'municipio',
        'pais',
        'telefono',
        'lenguaje',
        'tipoDocumento',
        'tipoEntorno',
        'tipoMoneda',
        'tipoOperacion',
        'tipoOrganizacion',
        'tipoRegimen',
        'tipoResponsabilidad',
        'impuesto',
        'registroMercantil',
        'logo',
        'logoLogin',
        'JPG',
        'estado',
    ];
}
