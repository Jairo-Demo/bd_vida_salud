<?php

namespace App\Services;

use App\Models\User;

/**
 * Servicio de Gestion de Menu y Arquitectura de Paquetes PUDS
 * Define los paquetes del sistema (P1 a P6) y filtra opciones segun el rol del usuario.
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
     * Definicion completa de los Paquetes del Sistema segun la metodologia PUDS
     */
    private static function todos(): array
    {
        return [
            [
                'codigo' => 'P1',
                'nombre' => 'Acceso, Usuarios y Seguridad',
                'corto' => 'Seguridad y Usuarios',
                'icono' => '🛡️',
                'descripcion' => 'Control de cuentas de acceso, asignacion de roles y bitacora inmutable de auditoria.',
                'opciones' => [
                    [
                        'nombre' => 'Gestion de Usuarios',
                        'ruta' => 'usuarios.index',
                        'descripcion' => 'Alta, modificacion y control de cuentas del personal.',
                        'roles' => ['Administrador'],
                        'ciclo' => 1,
                    ],
                    [
                        'nombre' => 'Bitacora del Sistema',
                        'ruta' => 'bitacora.index',
                        'descripcion' => 'Historial de auditoria (IP, fecha/hora, acciones y canal WEB).',
                        'roles' => ['Administrador'],
                        'ciclo' => 1,
                    ],
                ],
            ],
            [
                'codigo' => 'P2',
                'nombre' => 'Gestion de Pacientes',
                'corto' => 'Pacientes',
                'icono' => '👤',
                'descripcion' => 'Registro demografico, busqueda rapida por CI y expedientes clinicos base.',
                'opciones' => [
                    [
                        'nombre' => 'Catalogo de Pacientes',
                        'ruta' => 'pacientes.index',
                        'descripcion' => 'Registro y busqueda de expedientes clinicos de pacientes.',
                        'roles' => ['Administrador', 'Recepcionista', 'Medico'],
                        'ciclo' => 1,
                    ],
                    [
                        'nombre' => 'Registrar Nuevo Paciente',
                        'ruta' => 'pacientes.create',
                        'descripcion' => 'Formulario de registro con generacion de codigo PAC-XXX.',
                        'roles' => ['Administrador', 'Recepcionista'],
                        'ciclo' => 1,
                    ],
                ],
            ],
            [
                'codigo' => 'P3',
                'nombre' => 'Gestion Medica y Horarios',
                'corto' => 'Medicos y Horarios',
                'icono' => '🩺',
                'descripcion' => 'Especialidades clinicas, registro de facultativos y generacion automatica de fichas.',
                'opciones' => [
                    [
                        'nombre' => 'Especialidades Medicas',
                        'ruta' => 'especialidades.index',
                        'descripcion' => 'Catalogo de ramas de atencion clinica ofrecidas.',
                        'roles' => ['Administrador'],
                        'ciclo' => 1,
                    ],
                    [
                        'nombre' => 'Plantel Medico',
                        'ruta' => 'medicos.index',
                        'descripcion' => 'Perfiles medicos, registros profesionales y especialidades.',
                        'roles' => ['Administrador', 'Recepcionista'],
                        'ciclo' => 1,
                    ],
                    [
                        'nombre' => 'Horarios y Fichas de Atencion',
                        'ruta' => 'horarios.index',
                        'descripcion' => 'Jornadas de disponibilidad y calculo de turnos/fichas de 30 min.',
                        'roles' => ['Administrador', 'Recepcionista', 'Medico'],
                        'ciclo' => 1,
                    ],
                ],
            ],
            [
                'codigo' => 'P4',
                'nombre' => 'Programacion de Citas Medicas',
                'corto' => 'Citas Medicas',
                'icono' => '📅',
                'descripcion' => 'Reserva de citas sin solapamiento, agenda diaria y ciclo de vida de atencion.',
                'opciones' => [
                    [
                        'nombre' => 'Agendar Nueva Cita',
                        'ruta' => 'citas.create',
                        'descripcion' => 'Reserva de cita seleccionando especialidad, medico y ficha libre.',
                        'roles' => ['Administrador', 'Recepcionista', 'Paciente'],
                        'ciclo' => 1,
                    ],
                    [
                        'nombre' => 'Agenda y Control de Citas',
                        'ruta' => 'citas.index',
                        'descripcion' => 'Listado filtrable por fecha/medico, transiciones y cancelaciones.',
                        'roles' => ['Administrador', 'Recepcionista', 'Medico', 'Paciente'],
                        'ciclo' => 1,
                    ],
                ],
            ],
            [
                'codigo' => 'P5',
                'nombre' => 'Reportes Operativos y Estadisticos',
                'corto' => 'Reportes',
                'icono' => '📊',
                'descripcion' => 'Estadisticas de atencion, citas por especialidad y exportaciones a PDF.',
                'opciones' => [
                    [
                        'nombre' => 'Reporte de Citas por Periodo',
                        'ruta' => null,
                        'descripcion' => 'Cuantificacion de citas atendidas, canceladas e inasistencias.',
                        'roles' => ['Administrador', 'Recepcionista'],
                        'ciclo' => 2,
                    ],
                    [
                        'nombre' => 'Rendimiento por Medico',
                        'ruta' => null,
                        'descripcion' => 'Total de atenciones y ocupacion de fichas por especialista.',
                        'roles' => ['Administrador'],
                        'ciclo' => 2,
                    ],
                ],
            ],
            [
                'codigo' => 'P6',
                'nombre' => 'Atencion Clinica y Recetas',
                'corto' => 'Consultas Clinicas',
                'icono' => '💊',
                'descripcion' => 'Registro de consultas, diagnosticos, antecedentes clinicos y emision de recetas.',
                'opciones' => [
                    [
                        'nombre' => 'Registro de Consultas Medicas',
                        'ruta' => null,
                        'descripcion' => 'Sintomas, diagnostico, constantes vitales y observaciones.',
                        'roles' => ['Administrador', 'Medico'],
                        'ciclo' => 2,
                    ],
                    [
                        'nombre' => 'Emision de Recetas',
                        'ruta' => null,
                        'descripcion' => 'Prescripcion de medicamentos, dosificacion e indicaciones.',
                        'roles' => ['Administrador', 'Medico'],
                        'ciclo' => 2,
                    ],
                ],
            ],
        ];
    }
}
