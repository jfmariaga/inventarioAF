<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exportacion extends Model
{
    use HasFactory;

    protected $table = 'exportaciones';

    protected $fillable = [
        'solicitado_por',
        'empresa_id',
        'centro_costos_id',
        'estado',
        'archivo_path',
        'generado_en',
    ];

    protected $casts = [
        'generado_en' => 'datetime',
    ];

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function centroCostos(): BelongsTo
    {
        return $this->belongsTo(CentroCostos::class, 'centro_costos_id');
    }

    public function estaLista(): bool
    {
        return $this->estado === 'lista';
    }
}
