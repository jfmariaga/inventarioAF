<?php

namespace App\Livewire\Forms;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Livewire\Form;
use Livewire\WithFileUploads;

class CapturaActivoForm extends Form
{
    use WithFileUploads;

    public string $estado = 'verificado';

    public ?string $ubicacion = null;

    public ?string $observacion = null;

    /** @var UploadedFile|null */
    public $fotoEquipo = null;

    /** @var UploadedFile|null */
    public $fotoPlaca = null;

    public bool $yaTieneFotoEquipo = false;

    public bool $yaTieneFotoPlaca = false;

    /**
     * Reglas condicionales por estado (FR-011/FR-012): "verificado" exige
     * ubicación y la foto del equipo (salvo que ya exista una guardada);
     * la foto de la placa es opcional. "no_encontrado" exige solo observación.
     */
    public function rules(): array
    {
        $exigeEvidencia = $this->estado === 'verificado';

        return [
            'estado' => ['required', 'in:verificado,no_encontrado'],
            'ubicacion' => [Rule::requiredIf($exigeEvidencia), 'nullable', 'string', 'max:150'],
            'observacion' => [Rule::requiredIf($this->estado === 'no_encontrado'), 'nullable', 'string', 'max:2000'],
            'fotoEquipo' => [Rule::requiredIf($exigeEvidencia && ! $this->yaTieneFotoEquipo), 'nullable', 'image', 'max:10240'],
            'fotoPlaca' => ['nullable', 'image', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'ubicacion.required' => 'La ubicación es obligatoria para este estado.',
            'observacion.required' => 'La observación es obligatoria cuando el activo no se encuentra.',
            'fotoEquipo.required' => 'La foto del equipo es obligatoria para este estado.',
        ];
    }
}
