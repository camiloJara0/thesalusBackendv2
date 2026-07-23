<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePacienteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'info_usuario.name' => 'required|string|max:255',
            'info_usuario.No_document' => 'required|integer',
            'info_usuario.type_doc' => 'required|string|max:10',
            'info_usuario.celular' => 'required|integer',
            'info_usuario.telefono' => 'nullable|integer',
            'info_usuario.nacimiento' => 'required|date',
            'info_usuario.direccion' => 'required|string|max:255',
            'info_usuario.municipio' => 'required|string|max:100',
            'info_usuario.departamento' => 'required|string|max:100',
            'info_usuario.barrio' => 'required|string|max:100',
            'info_usuario.zona' => 'required|string|max:50',
            'id_eps' => 'required|exists:eps,id',
            'genero' => 'required|string|max:50',
            'sexo' => 'required|string|max:50',
            'regimen' => 'required|string|max:50',
            'vulnerabilidad' => 'nullable|string|max:100',
            'convenio_id' => 'nullable|exist:convenios,id',
        ];
    }

    public function messages()
    {
        return [
            'info_usuario.name.required' => 'El nombre del paciente es obligatorio.',
            'info_usuario.No_document.required' => 'El número de documento es obligatorio.',
            'info_usuario.type_doc.required' => 'El tipo de documento es obligatorio.',
            'info_usuario.celular.required' => 'El celular es obligatorio.',
            'info_usuario.nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'info_usuario.direccion.required' => 'La dirección es obligatoria.',
            'info_usuario.municipio.required' => 'El municipio es obligatorio.',
            'info_usuario.departamento.required' => 'El departamento es obligatorio.',
            'info_usuario.barrio.required' => 'El barrio es obligatorio.',
            'info_usuario.zona.required' => 'La zona es obligatoria.',
            'id_eps.required' => 'La EPS es obligatoria.',
            'id_eps.exists' => 'La EPS seleccionada no es válida.',
            'convenios.exists' => 'El convenio seleccionado no es válido.',
            'genero.required' => 'El género es obligatorio.',
            'sexo.required' => 'El sexo es obligatorio.',
            'regimen.required' => 'El régimen es obligatorio.',
        ];
    }
}
