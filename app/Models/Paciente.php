<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;
    public function infoUsuario(){
        return $this->belongsTo(InformacionUser::class, 'id_infoUsuario');
    }
    public function eps(){
        return $this->belongsTo(Eps::class, 'id_eps');
    }
    public function antecedente(){
        return $this->hasMany(Antecedente::class, 'id_paciente');
    }
    public function convenios()
    {
        return $this->belongsToMany(Convenio::class, 'paciente_has_convenios', 'id_paciente', 'id_convenio');
    }
    public function citas()
    {
        return $this->hasMany(Cita::class, 'id_paciente');
    }
    public function planManejoProcedimientos()
    {
        return $this->hasMany(Plan_manejo_procedimiento::class, 'id_paciente');
    }
    public function historiaClinica()
    {
        return $this->hasMany(Historia_Clinica::class, 'id_paciente');
    }
}

