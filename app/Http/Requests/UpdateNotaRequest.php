<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:notas,id',
            'direccion' => 'required|string|max:255',
            'fecha_nota' => 'required|date',
            'hora_nota' => 'required|date_format:H:i',
            'tipoAnalisis' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El ID de la nota es obligatorio.',
            'id.exists' => 'La nota no existe.',
            'direccion.required' => 'La dirección es obligatoria.',
            'fecha_nota.required' => 'La fecha de la nota es obligatoria.',
            'hora_nota.required' => 'La hora de la nota es obligatoria.',
        ];
    }
}
