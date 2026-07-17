<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KardexCampo extends Model
{
    use HasFactory;

    protected $table = 'kardex_campos';
    protected $fillable = [
        'nombre',
        'titulo',
        'tipo',
        'opciones',
        'descripcion',
        'valor_defecto',
        'activo',
    ];
    protected $casts = [
        'opciones' => 'array',
        'activo'   => 'boolean',
    ];

    public function plantillaCampos()
    {
        return $this->hasMany(KardexPlantillaCampo::class, 'id_campo');
    }

    public function plantillas()
    {
        return $this->belongsToMany(KardexPlantilla::class, 'kardex_plantilla_campos', 'id_campo', 'id_plantilla');
    }

    public function registros()
    {
        return $this->hasMany(KardexRegistro::class, 'id_campo');
    }
}
