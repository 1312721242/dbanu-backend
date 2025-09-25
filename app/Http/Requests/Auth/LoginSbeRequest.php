<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginSbeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],

            // Campos extra para registrar en cpu_personas
            'cedula' => ['nullable', 'string', 'max:20'],
            'nombres' => ['nullable', 'string', 'max:255'],
            'nacionalidad' => ['nullable', 'string'],
            'provincia' => ['nullable', 'string'],
            'ciudad' => ['nullable', 'string'],
            'parroquia' => ['nullable', 'string'],
            'direccion' => ['nullable', 'string'],
            'sexo' => ['nullable', 'string', 'max:1'],
            'fechanaci' => ['nullable', 'date'],
            'celular' => ['nullable', 'string'],
            'tipoetnia' => ['nullable', 'string'],
            'discapacidad' => ['nullable', 'string'],
            'tipo_discapacidad' => ['nullable', 'integer'],
            'porcentaje_discapacidad' => ['nullable', 'numeric'],
            'codigo_persona' => ['nullable', 'string'],
            'imagen' => ['nullable', 'string'],
            'id_clasificacion_tipo_usuario' => ['nullable', 'integer'],
            'ocupacion' => ['nullable', 'string'],
            'bono_desarrollo' => ['nullable', 'string'],
            'estado_civil' => ['nullable', 'string'],
            'id_tipo_usuario' => ['nullable', 'integer'],
            'genero' => ['nullable', 'string'],
            'carnet_conadis' => ['nullable', 'boolean'],
        ];
    }
}
