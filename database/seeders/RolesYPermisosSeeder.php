<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesYPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [
            'inventario.ver',
            'inventario.capturar',
            'inventario.exportar',
            'cumplimiento.ver',
            'admin.usuarios',
            'admin.roles',
            'admin.periodos',
            'admin.catalogos',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Administrador: acceso a todo.
        $administrador = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $administrador->syncPermissions($permisos);

        // Inventariador: solamente inventariar (ver + capturar activos), sin cumplimiento ni administración.
        $inventariador = Role::firstOrCreate(['name' => 'Inventariador', 'guard_name' => 'web']);
        $inventariador->syncPermissions(['inventario.ver', 'inventario.capturar']);

        // Consulta: solo cumplimiento por ahora.
        $consulta = Role::firstOrCreate(['name' => 'Consulta', 'guard_name' => 'web']);
        $consulta->syncPermissions(['cumplimiento.ver']);
    }
}
