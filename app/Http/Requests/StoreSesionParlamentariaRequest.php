<?php

namespace App\Http\Requests;

use App\Models\SesionParlamentaria;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSesionParlamentariaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', SesionParlamentaria::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:50'],
            'fecha_inicio_programada' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
