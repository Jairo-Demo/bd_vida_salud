<?php

namespace App\Services;

use App\Models\User;

/**
 * Servicio de Gestion de Menu y Arquitectura de Paquetes PUDS
 * Define los 8 paquetes del sistema (P1 a P8, Word Cap. 5.1) y filtra opciones segun el rol del usuario.
 * Las opciones cuya ruta aun no existe se muestran como 'Proximo ciclo' y se activan
 * automaticamente cuando se registra la ruta en routes/web.php (Route::has).
 */
class MenuService
{
    /**
     * Retorna los paquetes y opciones visibles segun el rol del usuario autenticado
     */
    public static function paquetes(?User $usuario): array
    {
        if (!$usuario) {
            return [];
        }

        $rolNombre = $usuario->rol ? $usuario->rol->nombre : 'Paciente';
        $paquetesVisibles = [];

        foreach (self::todos() as $paquete) {
            // Filtrar las opciones del paquete permitidas para este rol
            $opcionesFiltradas = array_values(array_filter(
                $paquete['opciones'],
                fn ($opcion) => in_array($rolNombre, $opcion['roles'])
            ));

            // Si el paquete tiene al menos una opcion disponible para este rol, se incluye
            if (!empty($opcionesFiltradas)) {
                $paquete['opciones'] = $opcionesFiltradas;
                $paquetesVisibles[] = $paquete;
            }
        }

        return $paquetesVisibles;
    }

    /**
     * Definicion completa de los Paquetes del Sistema segun la metodologia PUDS (Word Cap. 5.1.1)
     */
    private static function todos(): array
    {
        return [
            [
                'codigo' => 'P1',
                'nombre' => 'Acceso, Usuarios y Seguridad',
                'corto' => 'Seguridad',
                'icono' => '🛡️',
                'descripcion' => 'Control de cuentas de acceso, roles y permisos, y bitacora de auditoria.',
                'opciones' => [
                    [
                        'nombre' => 'Gestion de Usuarios',
                        'ruta' => 'usuarios.index',
                        'descripcion' => 'Alta, modificacion y control de cuentas del personal.',
                        'roles' => ['Administrador'],
                        'ciclo' => 1,
                        'cu' => 'CU03',
                    ],
                    [
                        'nombre' => 'Roles y Permisos',
                        'ruta' => 'roles.index',
                        'descripcion' => 'Matriz de permisos asignados a cada rol del sistema.',
                        'roles' => ['Administrador'],
                        'ciclo' => 2,
                        'cu' => 'CU04',
                    ],
                    [
                        'nombre' => 'Bitacora del Sistema',
                        'ruta' => 'bitacora.index',
                        'descripcion' => 'Historial de auditoria (IP, fecha/hora, acciones y canal WEB).',
                        'roles' => ['Administrador'],
                        'ciclo' => 1,
                        'cu' => 'CU05',
                    ],
                ],
            ],
            [
                'codigo' => 'P2',
                'nombre' => 'Gestion de Pacientes',
                'corto' => 'Pacientes',
                'icono' => '👤',
                'descripcion' => 'Registro de pacientes, busqueda por CI, historial de citas y perfil del paciente.',
                'opciones' => [
                    [
                        'nombre' => 'Catalogo de Pacientes',
                        'ruta' => 'pacientes.index',
                        'descripcion' => 'Registro y busqueda de expedientes de pacientes.',
                        'roles' => ['Administrador', 'Recepcionista', 'Medico'],
                        'ciclo' => 1,
                        'cu' => 'CU06, CU07',
                    ],
                    [
                        'nombre' => 'Registrar Nuevo Paciente',
                        'ruta' => 'pacientes.create',
                        'descripcion' => 'Formulario de registro con generacion de codigo PAC-XXX.',
                        'roles' => ['Administrador', 'Recepcionista'],
                        'ciclo' => 1,
                        'cu' => 'CU06',
                    ],
                    [
                        'nombre' => 'Historial de Citas',
                        'ruta' => 'historial.index',
                        'descripcion' => 'Citas del paciente con su estado, filtradas por fecha y estado.',
                        'roles' => ['Recepcionista', 'Paciente'],
                        'ciclo' => 2,
                        'cu' => 'CU08',
                    ],
                    [
                        'nombre' => 'Mi Perfil',
                        'ruta' => 'perfil.show',
                        'descripcion' => 'Datos de contacto y cambio de contrasena del paciente.',
                        'roles' => ['Paciente'],
                        'ciclo' => 2,
                        'cu' => 'CU09',
                    ],
                ],
            ],
            [
                'codigo' => 'P3',
                'nombre' => 'Gestion Medica y Horarios',
                'corto' => 'Médicos',
                'icono' => '🩺',
                'descripcion' => 'Especialidades, medicos, horarios con generacion de fichas y bloqueos de agenda.',
                'opciones' => [
                    [
                        'nombre' => 'Especialidades Medicas',
                        'ruta' => 'especialidades.index',
                        'descripcion' => 'Catalogo de ramas de atencion clinica ofrecidas.',
                        'roles' => ['Administrador'],
                        'ciclo' => 1,
                        'cu' => 'CU10',
                    ],
                    [
                        'nombre' => 'Plantel Medico',
                        'ruta' => 'medicos.index',
                        'descripcion' => 'Perfiles medicos, registros profesionales y especialidades.',
                        'roles' => ['Administrador', 'Recepcionista'],
                        'ciclo' => 1,
                        'cu' => 'CU11, CU12',
                    ],
                    [
                        'nombre' => 'Horarios y Fichas de Atencion',
                        'ruta' => 'horarios.index',
                        'descripcion' => 'Jornadas de atencion y calculo de fichas segun la duracion de la sesion.',
                        'roles' => ['Administrador', 'Recepcionista', 'Medico'],
                        'ciclo' => 1,
                        'cu' => 'CU13, CU14',
                    ],
                    [
                        'nombre' => 'Bloqueos de Agenda',
                        'ruta' => 'bloqueos.index',
                        'descripcion' => 'Feriados, vacaciones y permisos en los que no se ofrecen fichas.',
                        'roles' => ['Administrador'],
                        'ciclo' => 2,
                        'cu' => 'CU30',
                    ],
                ],
            ],
            [
                'codigo' => 'P4',
                'nombre' => 'Programacion de Citas',
                'corto' => 'Citas',
                'icono' => '📅',
                'descripcion' => 'Reserva de citas sin solapamiento, reprogramacion y cancelacion.',
                'opciones' => [
                    [
                        'nombre' => 'Agendar Nueva Cita',
                        'ruta' => 'citas.create',
                        'descripcion' => 'Reserva de cita seleccionando especialidad, medico y ficha libre.',
                        'roles' => ['Administrador', 'Recepcionista', 'Paciente'],
                        'ciclo' => 1,
                        'cu' => 'CU15',
                    ],
                    [
                        'nombre' => 'Listado de Citas',
                        'ruta' => 'citas.index',
                        'descripcion' => 'Consulta de citas; desde el detalle se reprograman o cancelan.',
                        'roles' => ['Administrador', 'Recepcionista', 'Medico', 'Paciente'],
                        'ciclo' => 1,
                        'cu' => 'CU15 - CU17',
                    ],
                ],
            ],
            [
                'codigo' => 'P5',
                'nombre' => 'Agenda y Asistencia',
                'corto' => 'Agenda',
                'icono' => '🗓️',
                'descripcion' => 'Agenda diaria por medico, confirmacion de citas y registro de inasistencias.',
                'opciones' => [
                    [
                        'nombre' => 'Agenda del Dia',
                        'ruta' => 'agenda.index',
                        'descripcion' => 'Citas por medico y fecha; confirmar asistencia o registrar inasistencia.',
                        'roles' => ['Recepcionista', 'Medico'],
                        'ciclo' => 2,
                        'cu' => 'CU18 - CU20',
                    ],
                ],
            ],
            [
                'codigo' => 'P6',
                'nombre' => 'Reportes',
                'corto' => 'Reportes',
                'icono' => '📊',
                'descripcion' => 'Reportes de citas por estado, medico y especialidad con exportacion a PDF y Excel.',
                'opciones' => [
                    [
                        'nombre' => 'Reportes de Citas',
                        'ruta' => 'reportes.index',
                        'descripcion' => 'Totales por estado, medico y especialidad en un periodo.',
                        'roles' => ['Administrador'],
                        'ciclo' => 2,
                        'cu' => 'CU21, CU22',
                    ],
                ],
            ],
            [
                'codigo' => 'P7',
                'nombre' => 'Atencion Medica e Historia Clinica',
                'corto' => 'Atención',
                'icono' => '💊',
                'descripcion' => 'Consultas, alergias y antecedentes, recetas con verificacion de alergias e historia clinica.',
                'opciones' => [
                    [
                        'nombre' => 'Registrar Atencion',
                        'ruta' => 'consultas.index',
                        'descripcion' => 'Citas confirmadas del medico para registrar la consulta.',
                        'roles' => ['Medico'],
                        'ciclo' => 2,
                        'cu' => 'CU23',
                    ],
                    [
                        'nombre' => 'Alergias y Antecedentes',
                        'ruta' => 'antecedentes.index',
                        'descripcion' => 'Alergias, antecedentes y medicamentos habituales del paciente.',
                        'roles' => ['Medico'],
                        'ciclo' => 2,
                        'cu' => 'CU24',
                    ],
                    [
                        'nombre' => 'Recetas Medicas',
                        'ruta' => 'recetas.index',
                        'descripcion' => 'Emision de recetas con alerta de alergias e impresion en PDF.',
                        'roles' => ['Medico'],
                        'ciclo' => 2,
                        'cu' => 'CU25, CU26',
                    ],
                    [
                        'nombre' => 'Catalogos Clinicos',
                        'ruta' => 'catalogos.index',
                        'descripcion' => 'Catalogos de medicamentos y alergias.',
                        'roles' => ['Administrador'],
                        'ciclo' => 2,
                        'cu' => 'CU27',
                    ],
                    [
                        'nombre' => 'Historia Clinica',
                        'ruta' => 'historia.index',
                        'descripcion' => 'Consultas, diagnosticos, recetas, alergias y antecedentes del paciente.',
                        'roles' => ['Medico', 'Paciente'],
                        'ciclo' => 2,
                        'cu' => 'CU28',
                    ],
                ],
            ],
            [
                'codigo' => 'P8',
                'nombre' => 'Funciones Opcionales',
                'corto' => 'Opcionales',
                'icono' => '🎙️',
                'descripcion' => 'Comando de voz y recordatorios automaticos de citas.',
                'opciones' => [
                    [
                        'nombre' => 'Recordatorios de Citas',
                        'ruta' => 'recordatorios.index',
                        'descripcion' => 'Recordatorios enviados y pendientes por la tarea programada.',
                        'roles' => ['Administrador'],
                        'ciclo' => 2,
                        'cu' => 'CU31',
                    ],
                ],
            ],
        ];
    }
}
