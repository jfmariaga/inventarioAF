<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NovedadActivo extends Model
{
    use HasFactory;

    protected $table = 'novedades_activos';

    protected $fillable = [
        'denominacion',
        'numero_activo',
        'placa',
        'ubicacion_original',
        'ubicacion_id',
        'condicion',
        'motivo_desuso',
        'foto_equipo_path',
        'foto_placa_path',
        'observacion',
        'centro_costos_id',
        'fecha_capitalizacion',
        'estado',
        'activo_id',
        'creado_por',
        'completada_por',
        'completada_en',
    ];

    protected $casts = [
        'fecha_capitalizacion' => 'date',
        'completada_en' => 'datetime',
    ];

    public function centroCostos(): BelongsTo
    {
        return $this->belongsTo(CentroCostos::class, 'centro_costos_id');
    }

    public function ubicacionCatalogo(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }

    public function activo(): BelongsTo
    {
        return $this->belongsTo(Activo::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function completadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completada_por');
    }

    public function estaPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }
}
