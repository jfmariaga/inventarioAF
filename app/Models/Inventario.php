<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventario extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'abierto_por',
        'cerrado_por',
        'reabierto_por',
        'reabierto_en',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
        'reabierto_en' => 'datetime',
    ];

    public function abiertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'abierto_por');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function reabiertoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reabierto_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(InventarioDetalle::class);
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }
}
