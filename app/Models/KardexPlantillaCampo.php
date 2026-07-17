<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KardexPlantillaCampo extends Model
{
    use HasFactory;

    protected $table = 'kardex_plantilla_campos';
    protected $fillable = [
        'id_plantilla',
        'id_campo',
        'orden',
        'requerido',
    ];
    protected $casts = [
        'requerido' => 'boolean',
    ];

    public function plantilla()
    {
        return $this->belongsTo(KardexPlantilla::class, 'id_plantilla');
    }

    public function campo()
    {
        return $this->belongsTo(KardexCampo::class, 'id_campo');
    }
}
