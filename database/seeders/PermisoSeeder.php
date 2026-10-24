<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class PermisoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // P1: Usuarios y Seguridad
            ['codigo' => 'USUARIOS_VER', 'descripcion' => 'Ver listado y detalles de usuarios'],
            ['codigo' => 'USUARIOS_CREAR', 'descripcion' => 'Crear nuevos usuarios del personal'],
            ['codigo' => 'USUARIOS_EDITAR', 'descripcion' => 'Modificar datos de usuarios'],
            ['codigo' => 'USUARIOS_ELIMINAR', 'descripcion' => 'Desactivar o eliminar usuarios'],
            ['codigo' => 'BITACORA_VER', 'descripcion' => 'Consultar bitacora de auditoria del sistema'],

            // P2: Gestion de Pacientes
            ['codigo' => 'PACIENTES_VER', 'descripcion' => 'Consultar expediente e historial de pacientes'],
            ['codigo' => 'PACIENTES_CREAR', 'descripcion' => 'Registrar nuevos pacientes en el sistema'],
            ['codigo' => 'PACIENTES_EDITAR', 'descripcion' => 'Modificar datos clinicos y demograficos de pacientes'],
            ['codigo' => 'PACIENTES_ELIMINAR', 'descripcion' => 'Desactivar expedientes de pacientes'],

            // P3: Medicos, Especialidades y Horarios
            ['codigo' => 'ESPECIALIDADES_GESTIONAR', 'descripcion' => 'Crear y editar catalogo de especialidades'],
            ['codigo' => 'MEDICOS_VER', 'descripcion' => 'Ver catalogo y perfiles de medicos'],
            ['codigo' => 'MEDICOS_GESTIONAR', 'descripcion' => 'Crear y editar perfiles de medicos y sus especialidades'],
            ['codigo' => 'HORARIOS_GESTIONAR', 'descripcion' => 'Definir jornadas de atencion y generar fichas'],

            // P4: Programacion de Citas
            ['codigo' => 'CITAS_VER_TODAS', 'descripcion' => 'Ver la totalidad de citas de la clinica'],
            ['codigo' => 'CITAS_VER_PROPIAS', 'descripcion' => 'Ver unicamente las citas correspondientes a su perfil'],
            ['codigo' => 'CITAS_AGENDAR', 'descripcion' => 'Reservar y registrar nuevas citas medicas'],
            ['codigo' => 'CITAS_REPROGRAMAR', 'descripcion' => 'Cambiar fecha u hora de citas existentes'],
            ['codigo' => 'CITAS_CANCELAR', 'descripcion' => 'Cancelar citas programadas'],
            ['codigo' => 'CITAS_ATENDER', 'descripcion' => 'Registrar atencion clinica de una cita (Medico)'],
        ];

        foreach ($permisos as $p) {
            Permiso::updateOrCreate(['codigo' => $p['codigo']], $p);
        }

        // Asignacion de permisos a Roles
        $admin = Rol::where('nombre', 'Administrador')->first();
        $recepcion = Rol::where('nombre', 'Recepcionista')->first();
        $medico = Rol::where('nombre', 'Medico')->first();
        $paciente = Rol::where('nombre', 'Paciente')->first();

        $todosPermisos = Permiso::all();

        // 1. Admin: Todos los permisos
        if ($admin) {
            $admin->permisos()->sync($todosPermisos->pluck('id'));
        }

        // 2. Recepcionista: Pacientes, ver medicos/horarios, y gestion total de citas
        if ($recepcion) {
            $permisosRecep = Permiso::whereIn('codigo', [
                'PACIENTES_VER', 'PACIENTES_CREAR', 'PACIENTES_EDITAR',
                'MEDICOS_VER', 'HORARIOS_GESTIONAR',
                'CITAS_VER_TODAS', 'CITAS_AGENDAR', 'CITAS_REPROGRAMAR', 'CITAS_CANCELAR'
            ])->pluck('id');
            $recepcion->permisos()->sync($permisosRecep);
        }

        // 3. Medico: Ver sus pacientes, ver su horario/fichas, ver citas propias y atender
        if ($medico) {
            $permisosMed = Permiso::whereIn('codigo', [
                'PACIENTES_VER',
                'MEDICOS_VER',
                'CITAS_VER_PROPIAS', 'CITAS_ATENDER'
            ])->pluck('id');
            $medico->permisos()->sync($permisosMed);
        }

        // 4. Paciente: Ver citas propias, agendar y cancelar citas propias
        if ($paciente) {
            $permisosPac = Permiso::whereIn('codigo', [
                'CITAS_VER_PROPIAS', 'CITAS_AGENDAR', 'CITAS_CANCELAR'
            ])->pluck('id');
            $paciente->permisos()->sync($permisosPac);
        }
    }
}
