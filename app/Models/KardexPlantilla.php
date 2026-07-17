<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KardexPlantilla extends Model
{
    use HasFactory;

    protected $table = 'kardex_plantillas';
    protected $fillable = [
        'nombre',
        'descripcion',
        'icono',
        'activo',
    ];
    protected $casts = [
        'activo' => 'boolean',
    ];

    public function plantillaCampos()
    {
        return $this->hasMany(KardexPlantillaCampo::class, 'id_plantilla');
    }

    public function campos()
    {
        return $this->belongsToMany(KardexCampo::class, 'kardex_plantilla_campos', 'id_plantilla', 'id_campo')
            ->withPivot('orden', 'requerido')
            ->orderBy('kardex_plantilla_campos.orden');
    }
}
