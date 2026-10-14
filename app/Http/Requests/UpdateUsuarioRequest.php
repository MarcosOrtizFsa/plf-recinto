<?php

namespace App\Http\Requests;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $usuario = $this->route('usuario');

        return $usuario instanceof User && ($this->user()?->can('update', $usuario) ?? false);
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
            'email' => ['required', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($this->route('usuario'))],
            'rol_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'banca_id' => ['nullable', 'integer', Rule::exists('bancas', 'id'), Rule::unique('usuarios', 'banca_id')->ignore($this->route('usuario'))],
            'contrasena' => ['nullable', 'string', 'min:8', 'confirmed'],
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
            if ($this->user()?->is($this->route('usuario')) && ! $this->boolean('activo')) {
                $validator->errors()->add('activo', 'No puede desactivar su propio usuario.');
            }
        }];
    }
}
