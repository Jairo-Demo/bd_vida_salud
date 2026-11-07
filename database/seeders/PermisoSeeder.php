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

            // ===================== CICLO 2 =====================
            // P1: Roles y permisos (CU04)
            ['codigo' => 'ROLES_GESTIONAR', 'descripcion' => 'Asignar permisos a los roles del sistema'],

            // P2: Historial y perfil del paciente (CU08, CU09)
            ['codigo' => 'PACIENTES_HISTORIAL', 'descripcion' => 'Consultar el historial de citas del paciente'],
            ['codigo' => 'PERFIL_GESTIONAR', 'descripcion' => 'Actualizar datos de contacto y contrasena propios (Paciente)'],

            // P3: Bloqueos de agenda (CU30)
            ['codigo' => 'BLOQUEOS_GESTIONAR', 'descripcion' => 'Registrar feriados, vacaciones y permisos de medicos'],

            // P5: Agenda y asistencia (CU18, CU19, CU20)
            ['codigo' => 'AGENDA_VER', 'descripcion' => 'Consultar la agenda de citas por medico y fecha'],
            ['codigo' => 'CITAS_CONFIRMAR', 'descripcion' => 'Confirmar la asistencia de una cita'],
            ['codigo' => 'CITAS_INASISTENCIA', 'descripcion' => 'Registrar la inasistencia del paciente'],

            // P6: Reportes (CU21, CU22)
            ['codigo' => 'REPORTES_GENERAR', 'descripcion' => 'Generar reportes de citas por estado, medico y especialidad'],
            ['codigo' => 'REPORTES_EXPORTAR', 'descripcion' => 'Exportar reportes a PDF o Excel'],

            // P7: Atencion medica e historia clinica (CU23 - CU28)
            ['codigo' => 'ANTECEDENTES_GESTIONAR', 'descripcion' => 'Registrar alergias, antecedentes y medicamentos habituales'],
            ['codigo' => 'RECETAS_EMITIR', 'descripcion' => 'Emitir recetas con verificacion de alergias'],
            ['codigo' => 'CATALOGOS_GESTIONAR', 'descripcion' => 'Mantener catalogos de medicamentos y alergias'],
            ['codigo' => 'HISTORIA_CLINICA_VER', 'descripcion' => 'Consultar la historia clinica del paciente'],

            // P8: Funciones opcionales (CU29, CU31)
            ['codigo' => 'COMANDO_VOZ', 'descripcion' => 'Ejecutar comandos de voz en el sistema'],
            ['codigo' => 'RECORDATORIOS_GESTIONAR', 'descripcion' => 'Consultar y reenviar recordatorios de citas'],
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
                'CITAS_VER_TODAS', 'CITAS_AGENDAR', 'CITAS_REPROGRAMAR', 'CITAS_CANCELAR',
                // Ciclo 2
                'PACIENTES_HISTORIAL', 'AGENDA_VER', 'CITAS_CONFIRMAR', 'CITAS_INASISTENCIA', 'COMANDO_VOZ'
            ])->pluck('id');
            $recepcion->permisos()->sync($permisosRecep);
        }

        // 3. Medico: Ver sus pacientes, ver su horario/fichas, ver citas propias y atender
        if ($medico) {
            $permisosMed = Permiso::whereIn('codigo', [
                'PACIENTES_VER',
                'MEDICOS_VER',
                'CITAS_VER_PROPIAS', 'CITAS_ATENDER',
                // Ciclo 2
                'AGENDA_VER', 'ANTECEDENTES_GESTIONAR', 'RECETAS_EMITIR', 'HISTORIA_CLINICA_VER', 'COMANDO_VOZ'
            ])->pluck('id');
            $medico->permisos()->sync($permisosMed);
        }

        // 4. Paciente: Ver citas propias, agendar y cancelar citas propias
        if ($paciente) {
            $permisosPac = Permiso::whereIn('codigo', [
                'CITAS_VER_PROPIAS', 'CITAS_AGENDAR', 'CITAS_CANCELAR',
                // Ciclo 2 (solo sobre su propia informacion)
                'PACIENTES_HISTORIAL', 'PERFIL_GESTIONAR', 'HISTORIA_CLINICA_VER', 'COMANDO_VOZ'
            ])->pluck('id');
            $paciente->permisos()->sync($permisosPac);
        }
    }
}
