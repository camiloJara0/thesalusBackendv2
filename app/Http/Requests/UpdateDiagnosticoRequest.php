<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiagnosticoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_paciente' => 'required|exists:pacientes,id',
            'id_profesional' => 'required|exists:profesionals,id',
            'codigoCIE_10' => 'required|string|max:20',
            'CIE_10' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'id_paciente.required' => 'El paciente es obligatorio.',
            'id_profesional.required' => 'El profesional es obligatorio.',
            'codigoCIE_10.required' => 'El código CIE-10 es obligatorio.',
            'CIE_10.required' => 'La descripción CIE-10 es obligatoria.',
        ];
    }
}
