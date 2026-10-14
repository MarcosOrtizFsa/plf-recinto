<?php

namespace App\Http\Requests;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
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
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'rol_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'banca_id' => ['nullable', 'integer', Rule::exists('bancas', 'id'), Rule::unique('usuarios', 'banca_id')],
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
            'activo' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $rol = Rol::query()->find($this->integer('rol_id'));
            if ($rol?->slug === 'legisladores' && ! $this->filled('banca_id')) {
                $validator->errors()->add('banca_id', 'Los legisladores deben tener una banca asignada.');
            }
            if ($rol && $rol->slug !== 'legisladores' && $this->filled('banca_id')) {
                $validator->errors()->add('banca_id', 'Sólo los legisladores pueden tener una banca asignada.');
            }
        }];
    }
}
