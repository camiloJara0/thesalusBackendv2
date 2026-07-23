<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnalisisRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|exists:analises,id',
            'analisis' => 'nullable|string',
            'observacion' => 'nullable|string',
            'motivo' => 'nullable|string',
            'tipoAnalisis' => 'nullable|string|max:100',
            'tratamiento' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El ID del análisis es obligatorio.',
            'id.exists' => 'El análisis no existe.',
        ];
    }
}
