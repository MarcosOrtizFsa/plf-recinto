<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVotacionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->puedeConfigurarRecinto() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sesion_parlamentaria_id' => ['required', 'integer', 'exists:sesiones_parlamentarias,id'],
            'documento_id' => ['nullable', 'integer', 'exists:documentos,id'],
            'tema' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'metodo' => ['required', 'in:asistido,directo'],
            'duracion_segundos' => ['required', 'integer', 'min:10', 'max:3600'],
        ];
    }
}
