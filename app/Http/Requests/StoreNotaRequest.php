<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'direccion' => 'required|string|max:255',
            'fecha_nota' => 'required|date',
            'hora_nota' => 'required|date_format:H:i',
            'tipoAnalisis' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
            'direccion.required' => 'La dirección es obligatoria.',
            'fecha_nota.required' => 'La fecha de la nota es obligatoria.',
            'fecha_nota.date' => 'La fecha de la nota debe ser una fecha válida.',
            'hora_nota.required' => 'La hora de la nota es obligatoria.',
        ];
    }
}
