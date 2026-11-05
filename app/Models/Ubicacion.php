<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ubicacion extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'empresa_id',
        'ubicacion',
        'detalle_ubicacion',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function getEtiquetaAttribute(): string
    {
        if ($this->detalle_ubicacion === '') {
            return $this->ubicacion;
        }

        return "{$this->ubicacion} — {$this->detalle_ubicacion}";
    }
}
