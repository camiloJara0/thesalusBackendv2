<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEpsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $epsId = $this->route('eum') ? $this->route('eum')->id : null;

        return [
            'id' => 'required|exists:eps,id',
            'nombre' => 'required|string|max:255',
            'codigo' => 'nullable|string|max:50',
            'nit' => 'nullable|string|max:20',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El ID de la EPS es obligatorio.',
            'id.exists' => 'La EPS no existe.',
            'nombre.required' => 'El nombre es obligatorio.',
        ];
    }
}
