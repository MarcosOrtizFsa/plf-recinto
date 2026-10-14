<?php

namespace App\Http\Requests;

use App\Models\Documento;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Documento::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'archivo' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf,application/x-pdf', 'max:'.config('recinto.documentos.tamano_maximo_kb')],
        ];
    }

    public function messages(): array
    {
        return ['archivo.mimes' => 'El archivo debe ser un PDF.', 'archivo.mimetypes' => 'El contenido del archivo no corresponde a un PDF.', 'archivo.max' => 'El PDF supera el tamaño máximo permitido.'];
    }
}
