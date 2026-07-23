<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalisisRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id_historia' => 'required|exists:historia__clinicas,id',
            'id_medico' => 'required|exists:profesionals,id',
            'id_servicio' => 'required|exists:servicio,id',
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
            'id_historia.required' => 'La historia clínica es obligatoria.',
            'id_historia.exists' => 'La historia clínica no existe.',
            'id_medico.required' => 'El médico es obligatorio.',
            'id_medico.exists' => 'El médico seleccionado no es válido.',
            'id_servicio.required' => 'El servicio es obligatorio.',
            'id_servicio.exists' => 'El servicio seleccionado no es válido.',
        ];
    }
}
