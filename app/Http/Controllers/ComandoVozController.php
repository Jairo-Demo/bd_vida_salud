<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Services\BitacoraService;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Comandos de voz (Paquete P8 / CU29 Ejecutar comando de voz).
 * El navegador transcribe el audio (Web Speech API) y envía el texto; aquí se identifica la
 * intención, se valida que el rol tenga acceso y se devuelve la acción. Se registra con canal VOZ.
 */
class ComandoVozController extends Controller
{
    /** Frases clave → ruta (las frases más largas primero). */
    private const DESTINOS = [
        'nueva cita' => 'citas.create', 'agendar cita' => 'citas.create', 'programar cita' => 'citas.create', 'agendar' => 'citas.create',
        'nuevo paciente' => 'pacientes.create', 'registrar paciente' => 'pacientes.create',
        'historia clinica' => 'historia.index', 'historial de citas' => 'historial.index', 'historial' => 'historial.index',
        'mi perfil' => 'perfil.show', 'perfil' => 'perfil.show',
        'roles y permisos' => 'roles.index', 'permisos' => 'roles.index', 'roles' => 'roles.index',
        'bloqueos' => 'bloqueos.index', 'feriados' => 'bloqueos.index',
        'alergias' => 'antecedentes.index', 'antecedentes' => 'antecedentes.index',
        'atender' => 'consultas.index', 'atencion' => 'consultas.index', 'consultas' => 'consultas.index',
        'recetas' => 'recetas.index', 'receta' => 'recetas.index',
        'catalogos' => 'catalogos.index', 'medicamentos' => 'catalogos.index',
        'recordatorios' => 'recordatorios.index',
        'reportes' => 'reportes.index', 'reporte' => 'reportes.index',
        'agenda' => 'agenda.index',
        'citas' => 'citas.index', 'cita' => 'citas.index',
        'pacientes' => 'pacientes.index',
        'medicos' => 'medicos.index', 'especialidades' => 'especialidades.index', 'horarios' => 'horarios.index', 'fichas' => 'horarios.index',
        'usuarios' => 'usuarios.index', 'bitacora' => 'bitacora.index',
        'inicio' => 'dashboard', 'panel' => 'dashboard', 'menu' => 'dashboard',
    ];

    public function ejecutar(Request $request)
    {
        $request->validate(['texto' => 'required|string|max:200']);
        $original = trim($request->input('texto'));
        $texto = $this->normalizar($original);
        $permitidas = $this->rutasPermitidas();

        // 1) "buscar paciente <nombre>"
        if (preg_match('/^(?:buscar?|busca|encontrar|abrir)\s+(?:al?\s+|la\s+)?paciente\s+(.+)$/', $texto, $m)) {
            if (!in_array('pacientes.index', $permitidas, true)) {
                return $this->respuesta($original, false, 'Su rol no tiene permiso para buscar pacientes.');
            }
            $termino = trim($m[1]);
            $sql = "translate(lower(personas.nombres || ' ' || personas.apellidos), 'áéíóúüñ', 'aeiouun') ilike ?";
            $encontrados = Paciente::join('personas', 'personas.id', '=', 'pacientes.persona_id')
                ->where(fn ($q) => $q->whereRaw($sql, ["%{$termino}%"])->orWhere('personas.ci', 'ilike', "%{$termino}%")->orWhere('pacientes.codigo', 'ilike', "%{$termino}%"))
                ->select('pacientes.*')->limit(2)->get();

            if ($encontrados->count() === 1) {
                $p = $encontrados->first();
                return $this->respuesta($original, true, "Abriendo el expediente de {$p->nombre_completo}.", route('pacientes.show', $p), 'BUSCAR_PACIENTE');
            }
            $palabra = collect(explode(' ', $termino))->last();
            $msg = $encontrados->isEmpty() ? "No se encontró al paciente \"{$termino}\"." : "Hay varios pacientes que coinciden con \"{$termino}\".";
            return $this->respuesta($original, true, $msg . ' Mostrando la búsqueda.', route('pacientes.index', ['buscar' => $palabra]), 'BUSCAR_PACIENTE');
        }

        // 2) "ir a / abrir / mostrar <módulo>"
        $destino = preg_replace('/^(?:ir a(?:l)?|ve a|vamos a|abrir|abre|mostrar|muestra|ver)\s+(?:la\s+|el\s+|los\s+|las\s+|mi\s+)?/', '', $texto);
        foreach (self::DESTINOS as $frase => $ruta) {
            if (preg_match('/\b' . preg_quote($frase, '/') . '\b/', $destino)) {
                if (!Route::has($ruta)) {
                    break;
                }
                if (!in_array($ruta, $permitidas, true)) {
                    return $this->respuesta($original, false, "Su rol no tiene permiso para \"{$frase}\".");
                }
                $etiqueta = strtr($frase, ['historia clinica' => 'historia clínica', 'catalogos' => 'catálogos', 'bitacora' => 'bitácora',
                    'atencion' => 'atención', 'medicos' => 'médicos', 'menu' => 'menú']);
                return $this->respuesta($original, true, 'Abriendo ' . $etiqueta . '…', route($ruta), 'NAVEGAR');
            }
        }

        return $this->respuesta($original, false, 'Comando no reconocido. Pruebe: "ir a citas", "abrir agenda" o "buscar paciente Juan Mamani".');
    }

    /** Rutas visibles en el menú del usuario (rol + permisos), más el panel. */
    private function rutasPermitidas(): array
    {
        $rutas = ['dashboard'];
        foreach (MenuService::paquetes(Auth::user()) as $paquete) {
            foreach ($paquete['opciones'] as $o) {
                if (!empty($o['ruta']) && Route::has($o['ruta'])) {
                    $rutas[] = $o['ruta'];
                }
            }
        }
        return $rutas;
    }

    private function normalizar(string $t): string
    {
        $t = Str::lower(Str::ascii($t));
        $t = preg_replace('/[^a-z0-9\s]/', ' ', $t);
        return trim(preg_replace('/\s+/', ' ', $t));
    }

    private function respuesta(string $texto, bool $ok, string $mensaje, ?string $url = null, string $accion = 'COMANDO_NO_EJECUTADO')
    {
        BitacoraService::registrar(
            accion: $ok ? "VOZ_{$accion}" : 'VOZ_COMANDO_RECHAZADO',
            tabla: null,
            registroId: null,
            detalles: "Comando: \"{$texto}\" → {$mensaje}",
            canal: 'VOZ'
        );
        return response()->json(['ok' => $ok, 'mensaje' => $mensaje, 'url' => $url]);
    }
}
