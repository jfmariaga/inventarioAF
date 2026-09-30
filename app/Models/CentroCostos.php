<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentroCostos extends Model
{
    use HasFactory;

    protected $table = 'centros_costos';

    protected $fillable = [
        'codigo',
        'descripcion',
        'empresa_id',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function activos(): HasMany
    {
        return $this->hasMany(Activo::class);
    }
}
