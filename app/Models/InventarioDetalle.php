<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InventarioDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventario_id',
        'activo_id',
        'estado',
        'ubicacion',
        'observacion',
        'foto_equipo_path',
        'foto_placa_path',
        'foto_equipo_path_anterior',
        'foto_placa_path_anterior',
        'fotos_reemplazadas_en',
        'reservado_por',
        'reservado_en',
        'actualizado_por',
    ];

    protected $casts = [
        'fotos_reemplazadas_en' => 'datetime',
        'reservado_en' => 'datetime',
    ];

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    public function activo(): BelongsTo
    {
        return $this->belongsTo(Activo::class);
    }

    public function reservadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reservado_por');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Intenta adquirir la reserva exclusiva de este registro para el
     * usuario dado, mediante una escritura condicional (compare-and-swap)
     * que evita condiciones de carrera entre dos usuarios abriendo el
     * mismo activo casi al mismo tiempo (spec FR-014/FR-014a).
     *
     * Devuelve true si la reserva quedó asignada a $userId.
     */
    public function adquirirReserva(int $userId, int $minutosExpiracion): bool
    {
        $expiracion = Carbon::now()->subMinutes($minutosExpiracion);

        $filasAfectadas = DB::table('inventario_detalles')
            ->where('id', $this->id)
            ->where(function ($query) use ($userId, $expiracion) {
                $query->whereNull('reservado_por')
                    ->orWhere('reservado_por', $userId)
                    ->orWhere('reservado_en', '<', $expiracion);
            })
            ->update([
                'reservado_por' => $userId,
                'reservado_en' => Carbon::now(),
            ]);

        if ($filasAfectadas > 0) {
            $this->refresh();

            return true;
        }

        $this->refresh();

        return false;
    }

    public function liberarReserva(): void
    {
        $this->forceFill([
            'reservado_por' => null,
            'reservado_en' => null,
        ])->save();
    }

    public function reservadaPorOtro(int $userId, int $minutosExpiracion): bool
    {
        if ($this->reservado_por === null || $this->reservado_por === $userId) {
            return false;
        }

        return $this->reservado_en !== null
            && $this->reservado_en->gt(Carbon::now()->subMinutes($minutosExpiracion));
    }
}
