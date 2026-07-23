<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAntecedenteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_paciente' => 'required|exists:pacientes,id',
            'tipo' => 'required|string|max:100',
            'valor' => 'required|string|max:1000',
        ];
    }

    public function messages()
    {
        return [
            'id_paciente.required' => 'El paciente es obligatorio.',
            'id_paciente.exists' => 'El paciente seleccionado no es válido.',
            'tipo.required' => 'El tipo de antecedente es obligatorio.',
            'valor.required' => 'El valor del antecedente es obligatorio.',
        ];
    }
}
