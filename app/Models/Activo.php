<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activo extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_activo',
        'denominacion',
        'fecha_capitalizacion',
        'placa',
        'ubicacion_original',
        'centro_costos_id',
        'origen',
        'creado_por',
    ];

    protected $casts = [
        'fecha_capitalizacion' => 'date',
    ];

    public function centroCostos(): BelongsTo
    {
        return $this->belongsTo(CentroCostos::class, 'centro_costos_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function inventarioDetalles(): HasMany
    {
        return $this->hasMany(InventarioDetalle::class);
    }
}
