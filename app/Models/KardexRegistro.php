<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KardexRegistro extends Model
{
    use HasFactory;

    protected $table = 'kardex_registros';
    protected $fillable = [
        'id_paciente',
        'id_campo',
        'valor',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function campo()
    {
        return $this->belongsTo(KardexCampo::class, 'id_campo');
    }
}
