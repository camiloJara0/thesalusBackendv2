<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facturacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_empresa',
        'claveTecnica',
        'descripcion',
        'fechaInicial',
        'fechaHasta',
        'fechaResolucion',
        'no_resolucion',
        'numeroInicial',
        'numeroHasta',
        'prefijo',
        'tipoDocumento',
    ];

    public function empresa(){
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }
}
