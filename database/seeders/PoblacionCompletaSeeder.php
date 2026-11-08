<?php

namespace Database\Seeders;

use App\Models\Alergia;
use App\Models\Antecedente;
use App\Models\Bitacora;
use App\Models\BloqueoAgenda;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\DetalleReceta;
use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\Ficha;
use App\Models\HistorialEstadoCita;
use App\Models\HorarioAtencion;
use App\Models\Medicamento;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PacienteAlergia;
use App\Models\PacienteMedicamento;
use App\Models\Persona;
use App\Models\Receta;
use App\Models\Recordatorio;
use App\Models\Rol;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Maestro de Población Completa (Ciclo 2):
 * Puebla las 25 tablas del sistema con un mínimo de 10 registros cada una,
 * garantizando coherencia referencial, datos realistas y total coincidencia con el Word.
 */
class PoblacionCompletaSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdmin = Rol::where('nombre', 'Administrador')->firstOrFail();
        $rolRecep = Rol::where('nombre', 'Recepcionista')->firstOrFail();
        $rolMed   = Rol::where('nombre', 'Medico')->firstOrFail();
        $rolPac   = Rol::where('nombre', 'Paciente')->firstOrFail();

        $estProg = EstadoCita::where('nombre', 'Programada')->firstOrFail();
        $estConf = EstadoCita::where('nombre', 'Confirmada')->firstOrFail();
        $estAten = EstadoCita::where('nombre', 'Atendida')->firstOrFail();
        $estCanc = EstadoCita::where('nombre', 'Cancelada')->firstOrFail();
        $estInas = EstadoCita::where('nombre', 'Inasistencia')->firstOrFail();

        // =====================================================================
        // 1. ESPECIALIDADES MÉDICAS (10 registros)
        // =====================================================================
        $especialidadesData = [
            ['nombre' => 'Medicina General', 'nombre_normalizado' => 'medicina_general', 'descripcion' => 'Atención médica primaria y triaje clínico.'],
            ['nombre' => 'Pediatría', 'nombre_normalizado' => 'pediatria', 'descripcion' => 'Atención pediátrica y control de desarrollo infantil.'],
            ['nombre' => 'Ginecología y Obstetricia', 'nombre_normalizado' => 'ginecologia_y_obstetricia', 'descripcion' => 'Salud femenina y control prenatal.'],
            ['nombre' => 'Odontología', 'nombre_normalizado' => 'odontologia', 'descripcion' => 'Salud bucodental y profilaxis.'],
            ['nombre' => 'Cardiología', 'nombre_normalizado' => 'cardiologia', 'descripcion' => 'Enfermedades cardiovasculares e hipertensión.'],
            ['nombre' => 'Traumatología', 'nombre_normalizado' => 'traumatologia', 'descripcion' => 'Lesiones articulares y traumatismos.'],
            ['nombre' => 'Dermatología', 'nombre_normalizado' => 'dermatologia', 'descripcion' => 'Tratamiento de afecciones de la piel y mucosas.'],
            ['nombre' => 'Neurología', 'nombre_normalizado' => 'neurologia', 'descripcion' => 'Diagnóstico del sistema nervioso central y periférico.'],
            ['nombre' => 'Oftalmología', 'nombre_normalizado' => 'oftalmologia', 'descripcion' => 'Salud visual y patologías oculares.'],
            ['nombre' => 'Endocrinología', 'nombre_normalizado' => 'endocrinologia', 'descripcion' => 'Control de diabetes, tiroides y trastornos metabólicos.'],
        ];

        $espModels = [];
        foreach ($especialidadesData as $ed) {
            $espModels[] = Especialidad::updateOrCreate(['nombre_normalizado' => $ed['nombre_normalizado']], $ed + ['estado' => true]);
        }

        // =====================================================================
        // 2. ADMINISTRADOR Y PERSONAL DE RECEPCIÓN (3 personas / 3 usuarios)
        // =====================================================================
        $pAdmin = Persona::updateOrCreate(
            ['ci' => '11325356'],
            ['nombres' => 'Jhon Jairo', 'apellidos' => 'Patzi Balvin', 'telefono' => '67891791', 'correo' => '47896jhonpro@gmail.com', 'estado' => true]
        );
        $userAdmin = User::updateOrCreate(
            ['username' => 'admin'],
            ['persona_id' => $pAdmin->id, 'rol_id' => $rolAdmin->id, 'name' => 'Jhon Jairo Patzi Balvin', 'email' => '47896jhonpro@gmail.com', 'password' => Hash::make('admin123'), 'estado' => true]
        );

        $pRecep1 = Persona::updateOrCreate(
            ['ci' => '2000002'],
            ['nombres' => 'María', 'apellidos' => 'López Méndez', 'telefono' => '71122334', 'correo' => 'recepcion@vidasalud.com', 'estado' => true]
        );
        User::updateOrCreate(
            ['username' => 'recepcion'],
            ['persona_id' => $pRecep1->id, 'rol_id' => $rolRecep->id, 'name' => 'María López Méndez', 'email' => 'recepcion@vidasalud.com', 'password' => Hash::make('recep123'), 'estado' => true]
        );

        $pRecep2 = Persona::updateOrCreate(
            ['ci' => '2000003'],
            ['nombres' => 'Carla', 'apellidos' => 'Suárez Vaca', 'telefono' => '71122335', 'correo' => 'carla.recep@vidasalud.com', 'estado' => true]
        );
        User::updateOrCreate(
            ['username' => 'carla.recep'],
            ['persona_id' => $pRecep2->id, 'rol_id' => $rolRecep->id, 'name' => 'Carla Suárez Vaca', 'email' => 'carla.recep@vidasalud.com', 'password' => Hash::make('recep123'), 'estado' => true]
        );

        // =====================================================================
        // 3. PLANTEL MÉDICO (10 Médicos, 10 Personas, 10 Usuarios, 12 Asignaciones)
        // =====================================================================
        $medicosData = [
            ['ci' => '3000001', 'nombres' => 'Alejandro', 'apellidos' => 'Roca Melgar', 'correo' => 'dr.roca@vidasalud.com', 'user' => 'dr.roca', 'esp' => [0, 1], 'cod' => 'MED-001', 'mat' => 'COLMED-8491'],
            ['ci' => '3000002', 'nombres' => 'Patricia', 'apellidos' => 'Vargas Castro', 'correo' => 'dra.vargas@vidasalud.com', 'user' => 'dra.vargas', 'esp' => [2], 'cod' => 'MED-002', 'mat' => 'COLMED-8492'],
            ['ci' => '3000003', 'nombres' => 'Carlos', 'apellidos' => 'Gutiérrez Ortiz', 'correo' => 'dr.gutierrez@vidasalud.com', 'user' => 'dr.gutierrez', 'esp' => [3], 'cod' => 'MED-003', 'mat' => 'COLMED-8493'],
            ['ci' => '3000004', 'nombres' => 'Claudia', 'apellidos' => 'Morales Pinto', 'correo' => 'dra.morales@vidasalud.com', 'user' => 'dra.morales', 'esp' => [4], 'cod' => 'MED-004', 'mat' => 'COLMED-8494'],
            ['ci' => '3000005', 'nombres' => 'Fernando', 'apellidos' => 'Sosa Aguilera', 'correo' => 'dr.sosa@vidasalud.com', 'user' => 'dr.sosa', 'esp' => [5], 'cod' => 'MED-005', 'mat' => 'COLMED-8495'],
            ['ci' => '3000006', 'nombres' => 'Gabriela', 'apellidos' => 'Justiniano Paz', 'correo' => 'dra.justiniano@vidasalud.com', 'user' => 'dra.justiniano', 'esp' => [6], 'cod' => 'MED-006', 'mat' => 'COLMED-8496'],
            ['ci' => '3000007', 'nombres' => 'Hugo', 'apellidos' => 'Torrico Mercado', 'correo' => 'dr.torrico@vidasalud.com', 'user' => 'dr.torrico', 'esp' => [7], 'cod' => 'MED-007', 'mat' => 'COLMED-8497'],
            ['ci' => '3000008', 'nombres' => 'Lorena', 'apellidos' => 'Chávez Dorado', 'correo' => 'dra.chavez@vidasalud.com', 'user' => 'dra.chavez', 'esp' => [8], 'cod' => 'MED-008', 'mat' => 'COLMED-8498'],
            ['ci' => '3000009', 'nombres' => 'Mauricio', 'apellidos' => 'Banzer Ribera', 'correo' => 'dr.banzer@vidasalud.com', 'user' => 'dr.banzer', 'esp' => [9], 'cod' => 'MED-009', 'mat' => 'COLMED-8499'],
            ['ci' => '3000010', 'nombres' => 'Silvia', 'apellidos' => 'Añez Céspedes', 'correo' => 'dra.anez@vidasalud.com', 'user' => 'dra.anez', 'esp' => [0, 4], 'cod' => 'MED-010', 'mat' => 'COLMED-8500'],
        ];

        $medModels = [];
        foreach ($medicosData as $md) {
            $pMed = Persona::updateOrCreate(
                ['ci' => $md['ci']],
                ['nombres' => $md['nombres'], 'apellidos' => $md['apellidos'], 'telefono' => '7220000' . substr($md['ci'], -1), 'correo' => $md['correo'], 'estado' => true]
            );

            $med = Medico::updateOrCreate(
                ['persona_id' => $pMed->id],
                ['codigo' => $md['cod'], 'matricula_profesional' => $md['mat'], 'biografia' => 'Facultativo especialista con más de 10 años de experiencia clínica.', 'estado' => true]
            );

            User::updateOrCreate(
                ['username' => $md['user']],
                ['persona_id' => $pMed->id, 'rol_id' => $rolMed->id, 'name' => 'Dr(a). ' . $pMed->nombre_completo, 'email' => $md['correo'], 'password' => Hash::make('medico123'), 'estado' => true]
            );

            $syncEsps = [];
            foreach ($md['esp'] as $idxEsp) {
                if (isset($espModels[$idxEsp])) {
                    $syncEsps[$espModels[$idxEsp]->id] = ['estado' => true];
                }
            }
            $med->especialidades()->syncWithoutDetaching($syncEsps);
            $medModels[] = $med;
        }

        // =====================================================================
        // 4. HORARIOS DE ATENCIÓN Y FICHAS (10 Horarios, 80 Fichas)
        // =====================================================================
        $diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes'];
        $horariosCreados = [];

        foreach ($medModels as $i => $med) {
            $dia = $diasSemana[$i % count($diasSemana)];
            $hInicio = ($i % 2 === 0) ? '08:00:00' : '14:00:00';
            $hFin    = ($i % 2 === 0) ? '12:00:00' : '18:00:00';

            $horario = HorarioAtencion::updateOrCreate(
                ['medico_id' => $med->id, 'dia_semana' => $dia, 'hora_inicio' => $hInicio],
                ['hora_fin' => $hFin, 'duracion_cita_min' => 30, 'estado' => true]
            );

            // Generar 8 fichas de 30 min por cada horario
            $inicio = Carbon::parse($hInicio);
            $fin    = Carbon::parse($hFin);
            $numFicha = 1;
            while ($inicio->copy()->addMinutes(30)->lte($fin)) {
                $finSlot = $inicio->copy()->addMinutes(30);
                Ficha::updateOrCreate(
                    ['horario_id' => $horario->id, 'numero' => $numFicha],
                    ['hora_inicio' => $inicio->format('H:i:s'), 'hora_fin' => $finSlot->format('H:i:s'), 'estado' => true]
                );
                $inicio = $finSlot;
                $numFicha++;
            }
            $horariosCreados[] = $horario;
        }

        // =====================================================================
        // 5. PACIENTES (12 Pacientes, 12 Personas, 6 Cuentas de Acceso Paciente)
        // =====================================================================
        $pacientesData = [
            ['ci' => '5000001', 'nombres' => 'Juan', 'apellidos' => 'Mamani Condori', 'correo' => 'juan.paciente@gmail.com', 'user' => 'paciente1', 'sexo' => 'M', 'fn' => '1990-05-15', 'dir' => 'Av. Busch #450, Santa Cruz', 'sangre' => 'O+', 'alergias' => 'Ninguna conocida', 'em_nom' => 'Ana Condori', 'em_par' => 'Madre', 'em_tel' => '74455668'],
            ['ci' => '5000002', 'nombres' => 'Lucía', 'apellidos' => 'Fernández Ruiz', 'correo' => 'lucia.paciente@gmail.com', 'user' => 'paciente2', 'sexo' => 'F', 'fn' => '1998-11-20', 'dir' => 'Barrio Hamacas C/5 #12, Santa Cruz', 'sangre' => 'A+', 'alergias' => 'Penicilina', 'em_nom' => 'Mario Fernández', 'em_par' => 'Padre', 'em_tel' => '75566779'],
            ['ci' => '5000003', 'nombres' => 'Carlos', 'apellidos' => 'Rojas Melgar', 'correo' => 'carlos.rojas@gmail.com', 'user' => 'paciente3', 'sexo' => 'M', 'fn' => '1985-03-10', 'dir' => 'Av. Cristo Redentor 4to Anillo', 'sangre' => 'B+', 'alergias' => 'Ibuprofeno', 'em_nom' => 'Elena Rojas', 'em_par' => 'Esposa', 'em_tel' => '76600111'],
            ['ci' => '5000004', 'nombres' => 'María', 'apellidos' => 'Delgado Soliz', 'correo' => 'maria.delgado@gmail.com', 'user' => 'paciente4', 'sexo' => 'F', 'fn' => '1995-07-22', 'dir' => 'Barrio Equipetrol Calle 8 #34', 'sangre' => 'O-', 'alergias' => 'Látex', 'em_nom' => 'Jorge Delgado', 'em_par' => 'Hermano', 'em_tel' => '76600112'],
            ['ci' => '5000005', 'nombres' => 'Pedro', 'apellidos' => 'Arce Justiniano', 'correo' => 'pedro.arce@gmail.com', 'user' => 'paciente5', 'sexo' => 'M', 'fn' => '2001-09-05', 'dir' => 'Plan 3000 Barrio El Carmen', 'sangre' => 'A+', 'alergias' => 'Ninguna conocida', 'em_nom' => 'Rosa Justiniano', 'em_par' => 'Madre', 'em_tel' => '76600113'],
            ['ci' => '5000006', 'nombres' => 'Andrea', 'apellidos' => 'Vargas Pinto', 'correo' => 'andrea.vargas@gmail.com', 'user' => 'paciente6', 'sexo' => 'F', 'fn' => '1992-12-18', 'dir' => 'Villa 1ro de Mayo Calle 3', 'sangre' => 'O+', 'alergias' => 'Sulfametoxazol', 'em_nom' => 'Pablo Vargas', 'em_par' => 'Esposo', 'em_tel' => '76600114'],
            ['ci' => '5000007', 'nombres' => 'Jorge', 'apellidos' => 'Suárez Mercado', 'correo' => 'jorge.suarez@gmail.com', 'user' => null, 'sexo' => 'M', 'fn' => '1988-04-30', 'dir' => 'Av. Santos Dumont 3er Anillo', 'sangre' => 'AB+', 'alergias' => 'Mariscos', 'em_nom' => 'Sonia Mercado', 'em_par' => 'Tía', 'em_tel' => '76600115'],
            ['ci' => '5000008', 'nombres' => 'Sofía', 'apellidos' => 'Morales Céspedes', 'correo' => 'sofia.morales@gmail.com', 'user' => null, 'sexo' => 'F', 'fn' => '2005-01-14', 'dir' => 'Av. Banzer Km 8', 'sangre' => 'O+', 'alergias' => 'Polen', 'em_nom' => 'Daniel Morales', 'em_par' => 'Padre', 'em_tel' => '76600116'],
            ['ci' => '5000009', 'nombres' => 'Roberto', 'apellidos' => 'Salvatierra Paz', 'correo' => 'roberto.salv@gmail.com', 'user' => null, 'sexo' => 'M', 'fn' => '1979-08-25', 'dir' => 'Radial 10 Barrio San Antonio', 'sangre' => 'B-', 'alergias' => 'Diclofenaco', 'em_nom' => 'Marta Salvatierra', 'em_par' => 'Hija', 'em_tel' => '76600117'],
            ['ci' => '5000010', 'nombres' => 'Daniela', 'apellidos' => 'Camacho Ortiz', 'correo' => 'daniela.camacho@gmail.com', 'user' => null, 'sexo' => 'F', 'fn' => '1994-06-11', 'dir' => 'Av. San Martín Calle 5', 'sangre' => 'A-', 'alergias' => 'Maní', 'em_nom' => 'Lucas Camacho', 'em_par' => 'Hermano', 'em_tel' => '76600118'],
            ['ci' => '5000011', 'nombres' => 'Mateo', 'apellidos' => 'Guzmán Ribera', 'correo' => 'mateo.guzman@gmail.com', 'user' => null, 'sexo' => 'M', 'fn' => '2010-02-17', 'dir' => 'Av. Mutualista 2do Anillo', 'sangre' => 'O+', 'alergias' => 'Ninguna conocida', 'em_nom' => 'Carla Ribera', 'em_par' => 'Madre', 'em_tel' => '76600119'],
            ['ci' => '5000012', 'nombres' => 'Valeria', 'apellidos' => 'Rios Aguilera', 'correo' => 'valeria.rios@gmail.com', 'user' => null, 'sexo' => 'F', 'fn' => '1999-10-08', 'dir' => 'Barrio Sirari Calle Los Claveles', 'sangre' => 'O+', 'alergias' => 'Aspirina', 'em_nom' => 'Gonzalo Rios', 'em_par' => 'Padre', 'em_tel' => '76600120'],
        ];

        $pacModels = [];
        foreach ($pacientesData as $i => $pd) {
            $pPac = Persona::updateOrCreate(
                ['ci' => $pd['ci']],
                ['nombres' => $pd['nombres'], 'apellidos' => $pd['apellidos'], 'telefono' => '700100' . str_pad($i + 1, 2, '0', STR_PAD_LEFT), 'correo' => $pd['correo'], 'estado' => true]
            );

            $pac = Paciente::updateOrCreate(
                ['persona_id' => $pPac->id],
                [
                    'codigo' => 'PAC-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'fecha_nacimiento' => $pd['fn'],
                    'sexo' => $pd['sexo'],
                    'direccion' => $pd['dir'],
                    'tipo_sangre' => $pd['sangre'],
                    'otras_alergias' => $pd['alergias'],
                    'contacto_emerg_nombre' => $pd['em_nom'],
                    'contacto_emerg_parentesco' => $pd['em_par'],
                    'contacto_emerg_telefono' => $pd['em_tel'],
                    'estado' => true,
                ]
            );

            if ($pd['user']) {
                User::updateOrCreate(
                    ['username' => $pd['user']],
                    ['persona_id' => $pPac->id, 'rol_id' => $rolPac->id, 'name' => $pPac->nombre_completo, 'email' => $pd['correo'], 'password' => Hash::make('paciente123'), 'estado' => true]
                );
            }
            $pacModels[] = $pac;
        }

        // =====================================================================
        // 6. CATÁLOGO CLÍNICO: ALERGIAS Y MEDICAMENTOS (10 Alergias, 12 Medicamentos)
        // =====================================================================
        $alergiasData = [
            ['nombre' => 'Penicilina', 'tipo' => 'Medicamento', 'descripcion' => 'Reacción adversa severa a antibióticos betalactámicos.'],
            ['nombre' => 'Amoxicilina', 'tipo' => 'Medicamento', 'descripcion' => 'Sensibilidad a derivados aminopenicilínicos.'],
            ['nombre' => 'Ibuprofeno', 'tipo' => 'Medicamento', 'descripcion' => 'Hipersensibilidad a antiinflamatorios no esteroideos (AINEs).'],
            ['nombre' => 'Acido acetilsalicilico', 'tipo' => 'Medicamento', 'descripcion' => 'Intolerancia a la aspirina y salicilatos.'],
            ['nombre' => 'Diclofenaco', 'tipo' => 'Medicamento', 'descripcion' => 'Reacción alérgica a derivados fenilacéticos.'],
            ['nombre' => 'Sulfametoxazol', 'tipo' => 'Medicamento', 'descripcion' => 'Alergia conocida a sulfonamidas antibacterianas.'],
            ['nombre' => 'Latex', 'tipo' => 'Ambiental', 'descripcion' => 'Alergia por contacto con proteínas del caucho natural.'],
            ['nombre' => 'Mariscos', 'tipo' => 'Alimento', 'descripcion' => 'Alergia grave a crustáceos y moluscos (tropomiosina).'],
            ['nombre' => 'Polen', 'tipo' => 'Ambiental', 'descripcion' => 'Rinitis y conjuntivitis estacional por polen gramíneo.'],
            ['nombre' => 'Mani', 'tipo' => 'Alimento', 'descripcion' => 'Hipersensibilidad alimentaria severa con riesgo de anafilaxia.'],
        ];

        $alergiaModels = [];
        foreach ($alergiasData as $ad) {
            $alergiaModels[] = Alergia::updateOrCreate(['nombre' => $ad['nombre']], $ad);
        }

        $medicamentosData = [
            ['nombre_comercial' => 'Paracetamol', 'principio_activo' => 'Paracetamol', 'concentracion' => '500 mg', 'presentacion' => 'Comprimido'],
            ['nombre_comercial' => 'Ibuprofeno', 'principio_activo' => 'Ibuprofeno', 'concentracion' => '400 mg', 'presentacion' => 'Comprimido'],
            ['nombre_comercial' => 'Amoxil', 'principio_activo' => 'Amoxicilina', 'concentracion' => '500 mg', 'presentacion' => 'Cápsula'],
            ['nombre_comercial' => 'Penicilina G Benzatinica', 'principio_activo' => 'Penicilina', 'concentracion' => '1.200.000 UI', 'presentacion' => 'Inyectable'],
            ['nombre_comercial' => 'Aspirina', 'principio_activo' => 'Acido acetilsalicilico', 'concentracion' => '100 mg', 'presentacion' => 'Comprimido'],
            ['nombre_comercial' => 'Diclofenaco Sodico', 'principio_activo' => 'Diclofenaco', 'concentracion' => '50 mg', 'presentacion' => 'Comprimido'],
            ['nombre_comercial' => 'Loratadina', 'principio_activo' => 'Loratadina', 'concentracion' => '10 mg', 'presentacion' => 'Comprimido'],
            ['nombre_comercial' => 'Omeprazol', 'principio_activo' => 'Omeprazol', 'concentracion' => '20 mg', 'presentacion' => 'Cápsula'],
            ['nombre_comercial' => 'Metformina', 'principio_activo' => 'Metformina', 'concentracion' => '850 mg', 'presentacion' => 'Comprimido'],
            ['nombre_comercial' => 'Losartan Potasico', 'principio_activo' => 'Losartan', 'concentracion' => '50 mg', 'presentacion' => 'Comprimido'],
            ['nombre_comercial' => 'Salbutamol Aerosol', 'principio_activo' => 'Salbutamol', 'concentracion' => '100 mcg/dosis', 'presentacion' => 'Inhalador'],
            ['nombre_comercial' => 'Bactrim Forte', 'principio_activo' => 'Sulfametoxazol + Trimetoprima', 'concentracion' => '800/160 mg', 'presentacion' => 'Comprimido'],
        ];

        $medFarmaModels = [];
        foreach ($medicamentosData as $mfd) {
            $medFarmaModels[] = Medicamento::updateOrCreate(
                ['nombre_comercial' => $mfd['nombre_comercial'], 'concentracion' => $mfd['concentracion']],
                $mfd + ['estado' => true]
            );
        }

        // =====================================================================
        // 7. HISTORIA CLÍNICA: PACIENTE_ALERGIA (10 registros)
        // =====================================================================
        $pacAlergiasSeed = [
            ['pac_idx' => 1, 'ale_idx' => 0, 'sev' => 'Grave', 'reac' => 'Shock anafiláctico / Urticaria generalizada'], // Lucía alérgica a Penicilina
            ['pac_idx' => 2, 'ale_idx' => 2, 'sev' => 'Moderada', 'reac' => 'Broncoespasmo y edema palpebral'],
            ['pac_idx' => 3, 'ale_idx' => 6, 'sev' => 'Leve', 'reac' => 'Dermatitis por contacto y eritema'],
            ['pac_idx' => 5, 'ale_idx' => 5, 'sev' => 'Grave', 'reac' => 'Exantema maculopapular y fiebre'],
            ['pac_idx' => 6, 'ale_idx' => 7, 'sev' => 'Grave', 'reac' => 'Edema de glotis y dificultad respiratoria'],
            ['pac_idx' => 7, 'ale_idx' => 8, 'sev' => 'Leve', 'reac' => 'Rinorrea acuosa y prurito ocular'],
            ['pac_idx' => 8, 'ale_idx' => 4, 'sev' => 'Moderada', 'reac' => 'Epigastralgia severa y erupción cutánea'],
            ['pac_idx' => 9, 'ale_idx' => 9, 'sev' => 'Grave', 'reac' => 'Anafilaxia severa con hipotensión'],
            ['pac_idx' => 11, 'ale_idx' => 3, 'sev' => 'Moderada', 'reac' => 'Crisis asmática inducida por salicilatos'],
            ['pac_idx' => 0, 'ale_idx' => 1, 'sev' => 'Leve', 'reac' => 'Prurito cutáneo leve'],
        ];

        foreach ($pacAlergiasSeed as $pas) {
            if (isset($pacModels[$pas['pac_idx']], $alergiaModels[$pas['ale_idx']])) {
                PacienteAlergia::updateOrCreate(
                    ['paciente_id' => $pacModels[$pas['pac_idx']]->id, 'alergia_id' => $alergiaModels[$pas['ale_idx']]->id],
                    ['severidad' => $pas['sev'], 'reaccion' => $pas['reac'], 'fecha_registro' => now()->subMonths(6)->toDateString()]
                );
            }
        }

        // =====================================================================
        // 8. HISTORIA CLÍNICA: PACIENTE_MEDICAMENTO (10 registros)
        // =====================================================================
        $pacMedSeed = [
            ['pac_idx' => 0, 'med_idx' => 9, 'tipo' => 'Habitual', 'obs' => 'Losartán 50mg diario en ayunas por hipertensión leve.'],
            ['pac_idx' => 1, 'med_idx' => 3, 'tipo' => 'Contraindicado', 'obs' => 'PROHIBIDO: Alergia severa a penicilinas y derivados.'],
            ['pac_idx' => 2, 'med_idx' => 1, 'tipo' => 'Contraindicado', 'obs' => 'Contraindicado por intolerancia gástrica y broncoespasmo.'],
            ['pac_idx' => 3, 'med_idx' => 7, 'tipo' => 'Habitual', 'obs' => 'Omeprazol 20mg diario por reflujo gastroesofágico.'],
            ['pac_idx' => 4, 'med_idx' => 10, 'tipo' => 'Habitual', 'obs' => 'Salbutamol en aerosol por asma bronquial intermitente.'],
            ['pac_idx' => 5, 'med_idx' => 11, 'tipo' => 'Contraindicado', 'obs' => 'Contraindicado por hipersensibilidad a sulfas.'],
            ['pac_idx' => 6, 'med_idx' => 8, 'tipo' => 'Habitual', 'obs' => 'Metformina 850mg con el almuerzo para control glucémico.'],
            ['pac_idx' => 7, 'med_idx' => 6, 'tipo' => 'Habitual', 'obs' => 'Loratadina 10mg en época de floración.'],
            ['pac_idx' => 8, 'med_idx' => 5, 'tipo' => 'Contraindicado', 'obs' => 'Evitar AINEs por antecedentes de úlcera péptica.'],
            ['pac_idx' => 11, 'med_idx' => 4, 'tipo' => 'Contraindicado', 'obs' => 'Contraindicada aspirina por asma inducida.'],
        ];

        foreach ($pacMedSeed as $pms) {
            if (isset($pacModels[$pms['pac_idx']], $medFarmaModels[$pms['med_idx']])) {
                PacienteMedicamento::updateOrCreate(
                    ['paciente_id' => $pacModels[$pms['pac_idx']]->id, 'medicamento_id' => $medFarmaModels[$pms['med_idx']]->id],
                    ['tipo' => $pms['tipo'], 'observacion' => $pms['obs']]
                );
            }
        }

        // =====================================================================
        // 9. HISTORIA CLÍNICA: ANTECEDENTES (10 registros)
        // =====================================================================
        $antecedentesSeed = [
            ['pac_idx' => 0, 'tipo' => 'Patológico', 'desc' => 'Hipertensión arterial esencial diagnosticada hace 3 años en tratamiento.'],
            ['pac_idx' => 1, 'tipo' => 'Alergias', 'desc' => 'Antecedente de urticaria severa infantil tras administración de ampicilina.'],
            ['pac_idx' => 2, 'tipo' => 'Quirúrgico', 'desc' => 'Apendicectomía laparoscópica no complicada en 2018.'],
            ['pac_idx' => 3, 'tipo' => 'Familiar', 'desc' => 'Madre con antecedente de diabetes mellitus tipo 2.'],
            ['pac_idx' => 4, 'tipo' => 'Patológico', 'desc' => 'Asma bronquial alérgica de inicio en la infancia.'],
            ['pac_idx' => 5, 'tipo' => 'Quirúrgico', 'desc' => 'Cesárea segmentaria transversa en 2021.'],
            ['pac_idx' => 6, 'tipo' => 'Patológico', 'desc' => 'Diabetes mellitus tipo 2 bien controlada con dieta y fármacos orales.'],
            ['pac_idx' => 7, 'tipo' => 'Familiar', 'desc' => 'Padre con cardiopatía isquémica y colocación de stent coronario.'],
            ['pac_idx' => 8, 'tipo' => 'Patológico', 'desc' => 'Gastritis crónica atrófica confirmada por endoscopía.'],
            ['pac_idx' => 9, 'tipo' => 'Tóxico', 'desc' => 'Tabaquismo social activo (1 a 2 cigarrillos semanales).'],
        ];

        foreach ($antecedentesSeed as $as) {
            if (isset($pacModels[$as['pac_idx']])) {
                Antecedente::updateOrCreate(
                    ['paciente_id' => $pacModels[$as['pac_idx']]->id, 'descripcion' => $as['desc']],
                    ['tipo' => $as['tipo'], 'fecha_diagnostico' => now()->subYears(2)->toDateString()]
                );
            }
        }

        // =====================================================================
        // 10. BLOQUEOS DE AGENDA (10 registros - CU30)
        // =====================================================================
        $bloqueosSeed = [
            ['med_idx' => 0, 'tipo' => 'Capacitación', 'dias' => 10, 'mot' => 'Congreso Nacional de Medicina Interna y Atención Primaria.'],
            ['med_idx' => 1, 'tipo' => 'Vacaciones', 'dias' => 15, 'mot' => 'Periodo de descanso anual programado.'],
            ['med_idx' => 2, 'tipo' => 'Permiso', 'dias' => 20, 'mot' => 'Permiso institucional para jornada científica odontológica.'],
            ['med_idx' => 3, 'tipo' => 'Feriado', 'dias' => 25, 'mot' => 'Feriado nacional de efeméride departamental.'],
            ['med_idx' => 4, 'tipo' => 'Capacitación', 'dias' => 30, 'mot' => 'Taller intensivo de artroscopía y cirugía ortopédica.'],
            ['med_idx' => 5, 'tipo' => 'Vacaciones', 'dias' => 35, 'mot' => 'Vacaciones reglamentarias de fin de año.'],
            ['med_idx' => 6, 'tipo' => 'Permiso', 'dias' => 40, 'mot' => 'Comisión académica de postgrado universitario.'],
            ['med_idx' => 7, 'tipo' => 'Capacitación', 'dias' => 45, 'mot' => 'Seminario internacional de retina y microcirugía ocular.'],
            ['med_idx' => 8, 'tipo' => 'Permiso', 'dias' => 50, 'mot' => 'Asistencia a reunión del Colegio Médico Departamental.'],
            ['med_idx' => 9, 'tipo' => 'Otro', 'dias' => 55, 'mot' => 'Mantenimiento de consultorio y equipos médicos.'],
        ];

        foreach ($bloqueosSeed as $bs) {
            if (isset($medModels[$bs['med_idx']])) {
                $fIni = Carbon::today()->addDays($bs['dias']);
                BloqueoAgenda::updateOrCreate(
                    ['medico_id' => $medModels[$bs['med_idx']]->id, 'fecha_inicio' => $fIni->toDateString()],
                    ['fecha_fin' => $fIni->copy()->addDay()->toDateString(), 'tipo' => $bs['tipo'], 'dia_completo' => true, 'motivo' => $bs['mot']]
                );
            }
        }

        // =====================================================================
        // 11. CITAS MÉDICAS (21 Citas en todos los estados - CU14 a CU20)
        // =====================================================================
        $citasConfig = [
            // Citas Futuras (Programadas / Confirmadas)
            ['cod' => 'CM-0001', 'pac' => 0, 'med' => 0, 'esp' => 0, 'estado' => $estProg, 'dias' => 1, 'fnum' => 1, 'mot' => 'Control de rutina y chequeo de presión arterial.'],
            ['cod' => 'CM-0002', 'pac' => 1, 'med' => 1, 'esp' => 2, 'estado' => $estConf, 'dias' => 2, 'fnum' => 1, 'mot' => 'Control prenatal y ecografía obstétrica de rutina.'],
            ['cod' => 'CM-0003', 'pac' => 2, 'med' => 2, 'esp' => 3, 'estado' => $estProg, 'dias' => 3, 'fnum' => 1, 'mot' => 'Limpieza dental profunda y evaluación de caries.'],
            ['cod' => 'CM-0004', 'pac' => 3, 'med' => 3, 'esp' => 4, 'estado' => $estConf, 'dias' => 4, 'fnum' => 1, 'mot' => 'Palpitaciones y chequeo electrocardiográfico.'],
            ['cod' => 'CM-0005', 'pac' => 4, 'med' => 4, 'esp' => 5, 'estado' => $estProg, 'dias' => 5, 'fnum' => 1, 'mot' => 'Dolor en rodilla derecha tras práctica deportiva.'],

            // 10 Citas Atendidas (con Consultas Clínicas y Recetas Médicas - CU23 a CU25)
            // Dr. Alejandro Roca (med 0) tiene 4 citas atendidas con recetas
            ['cod' => 'CM-0006', 'pac' => 0, 'med' => 0, 'esp' => 0, 'estado' => $estAten, 'dias' => -1, 'fnum' => 1, 'mot' => 'Cefalea intensa pulsátil de 48 horas de evolución.'],
            ['cod' => 'CM-0007', 'pac' => 5, 'med' => 0, 'esp' => 0, 'estado' => $estAten, 'dias' => -1, 'fnum' => 2, 'mot' => 'Dolor de garganta, disfagia y fiebre de 38.5°C.'],
            ['cod' => 'CM-0008', 'pac' => 6, 'med' => 0, 'esp' => 0, 'estado' => $estAten, 'dias' => -2, 'fnum' => 1, 'mot' => 'Epigastralgia urente postprandial y acidez estomacal.'],
            ['cod' => 'CM-0009', 'pac' => 1, 'med' => 0, 'esp' => 0, 'estado' => $estAten, 'dias' => -2, 'fnum' => 2, 'mot' => 'Evaluación de dolor articular y chequeo general.'],

            // Otros médicos también tienen citas atendidas con recetas
            ['cod' => 'CM-0010', 'pac' => 7, 'med' => 1, 'esp' => 2, 'estado' => $estAten, 'dias' => -1, 'fnum' => 1, 'mot' => 'Control ginecológico y papanicolau de rutina.'],
            ['cod' => 'CM-0011', 'pac' => 8, 'med' => 2, 'esp' => 3, 'estado' => $estAten, 'dias' => -2, 'fnum' => 1, 'mot' => 'Caries oclusal en premolar superior y obturación.'],
            ['cod' => 'CM-0012', 'pac' => 9, 'med' => 3, 'esp' => 4, 'estado' => $estAten, 'dias' => -3, 'fnum' => 1, 'mot' => 'Control de hipertensión y ajuste de fármacos.'],
            ['cod' => 'CM-0013', 'pac' => 10, 'med' => 4, 'esp' => 5, 'estado' => $estAten, 'dias' => -4, 'fnum' => 1, 'mot' => 'Lumbalgia mecánica paravertebral y dolor ciático.'],
            ['cod' => 'CM-0014', 'pac' => 11, 'med' => 5, 'esp' => 6, 'estado' => $estAten, 'dias' => -2, 'fnum' => 1, 'mot' => 'Dermatitis atópica pruriginosa en flexuras.'],
            ['cod' => 'CM-0015', 'pac' => 2, 'med' => 6, 'esp' => 7, 'estado' => $estAten, 'dias' => -3, 'fnum' => 1, 'mot' => 'Cefalea tensional crónica recurrente.'],

            // Citas Canceladas (con motivo registrado - CU17)
            ['cod' => 'CM-0016', 'pac' => 3, 'med' => 1, 'esp' => 2, 'estado' => $estCanc, 'dias' => -1, 'fnum' => 2, 'mot' => 'Consulta ginecológica.', 'mcanc' => 'Paciente canceló con 24h de anticipación por motivos laborales.'],
            ['cod' => 'CM-0017', 'pac' => 4, 'med' => 2, 'esp' => 3, 'estado' => $estCanc, 'dias' => -2, 'fnum' => 2, 'mot' => 'Extracción de tercer molar.', 'mcanc' => 'Reprogramación solicitada por el paciente por cuadro gripal.'],
            ['cod' => 'CM-0018', 'pac' => 5, 'med' => 7, 'esp' => 8, 'estado' => $estCanc, 'dias' => -3, 'fnum' => 1, 'mot' => 'Control oftalmológico de agudeza visual.', 'mcanc' => 'Cancelación por viaje imprevisto de la paciente.'],

            // Citas con Inasistencia (No-Show - CU19)
            ['cod' => 'CM-0019', 'pac' => 6, 'med' => 8, 'esp' => 9, 'estado' => $estInas, 'dias' => -1, 'fnum' => 1, 'mot' => 'Control metabólico y tiroideo.'],
            ['cod' => 'CM-0020', 'pac' => 7, 'med' => 8, 'esp' => 9, 'estado' => $estInas, 'dias' => -2, 'fnum' => 1, 'mot' => 'Chequeo de glucosa e insulina basal.'],
            ['cod' => 'CM-0021', 'pac' => 8, 'med' => 9, 'esp' => 0, 'estado' => $estInas, 'dias' => -1, 'fnum' => 1, 'mot' => 'Control médico general anual.'],
        ];

        $citaModels = [];
        foreach ($citasConfig as $cfg) {
            $med = $medModels[$cfg['med']];
            $pac = $pacModels[$cfg['pac']];
            $esp = $espModels[$cfg['esp']];
            $fecha = Carbon::today()->addDays($cfg['dias'])->toDateString();

            // Buscar la ficha asignada al médico
            $horario = HorarioAtencion::where('medico_id', $med->id)->first();
            $ficha = Ficha::where('horario_id', $horario->id)->where('numero', $cfg['fnum'])->first()
                ?? Ficha::where('horario_id', $horario->id)->first();

            $cita = Cita::updateOrCreate(
                ['codigo' => $cfg['cod']],
                [
                    'paciente_id' => $pac->id,
                    'medico_id' => $med->id,
                    'especialidad_id' => $esp->id,
                    'ficha_id' => $ficha->id,
                    'estado_id' => $cfg['estado']->id,
                    'usuario_registro_id' => $userAdmin->id,
                    'fecha' => $fecha,
                    'hora_inicio' => $ficha->hora_inicio,
                    'hora_fin' => $ficha->hora_fin,
                    'motivo' => $cfg['mot'],
                    'modalidad' => 'Presencial',
                    'motivo_cancelacion' => $cfg['mcanc'] ?? null,
                    'fecha_creacion' => now()->subDays(abs($cfg['dias']) + 1),
                ]
            );

            // Historial de Estados de la Cita (auditoría del flujo de estados)
            HistorialEstadoCita::firstOrCreate(
                ['cita_id' => $cita->id, 'estado_nuevo_id' => $cfg['estado']->id],
                [
                    'estado_anterior_id' => ($cfg['estado']->id !== $estProg->id) ? $estProg->id : null,
                    'usuario_id' => $userAdmin->id,
                    'fecha_hora' => now()->subDays(abs($cfg['dias'])),
                    'motivo' => 'Registro de transición a estado ' . $cfg['estado']->nombre,
                ]
            );

            $citaModels[] = $cita;
        }

        // =====================================================================
        // 12. CONSULTAS CLÍNICAS Y RECETAS (10 Consultas y 10 Recetas - CU23 a CU25)
        // Mapeadas exactamente a las 10 citas atendidas (CM-0006 a CM-0015, indices 5 a 14)
        // =====================================================================
        $consultasData = [
            // Citas atendidas del Dr. Alejandro Roca (indices 5, 6, 7, 8)
            ['c_idx' => 5, 'diag' => 'Cefalea tensional moderada por estrés laboral', 'sint' => 'Cefalea opresiva en banda, fotofobia leve, tensión occipital.', 'pa' => '120/80', 't' => 36.6, 'p' => 70.2, 'ind' => 'Reposo, hidratación oral de 2L/día y pausas visuales.', 'farma' => 0, 'dosis' => 'Paracetamol 500 mg cada 8 horas por 3 días.'],
            ['c_idx' => 6, 'diag' => 'Faringoamigdalitis aguda eritematosa', 'sint' => 'Odinofagia intensa, exudado amigdalino, fiebre 38.5°C.', 'pa' => '115/75', 't' => 38.5, 'p' => 64.0, 'ind' => 'Gargarismos salinos y reposo relativo.', 'farma' => 2, 'dosis' => 'Amoxil 500 mg cada 8 horas por 7 días.'],
            ['c_idx' => 7, 'diag' => 'Gastritis aguda erosiva no complicada', 'sint' => 'Dolor punzante epigástrico y acidez estomacal matutina.', 'pa' => '125/80', 't' => 36.8, 'p' => 78.5, 'ind' => 'Dieta blanda gástrica, suspender irritantes.', 'farma' => 7, 'dosis' => 'Omeprazol 20 mg en ayunas por 14 días.'],
            ['c_idx' => 8, 'diag' => 'Artralgia mecánica de rodilla bilateral', 'sint' => 'Dolor articular post esfuerzo sin signos de derrame sinovial.', 'pa' => '120/75', 't' => 36.5, 'p' => 62.0, 'ind' => 'Crioterapia 15 minutos dos veces al día y reposo.', 'farma' => 1, 'dosis' => 'Ibuprofeno 400 mg cada 12 horas con comidas por 4 días.'],

            // Citas atendidas de los demás médicos especialistas (indices 9 a 14)
            ['c_idx' => 9, 'diag' => 'Control prenatal de 2do trimestre eutócico', 'sint' => 'Asintomática, movimientos fetales activos normales.', 'pa' => '110/70', 't' => 36.5, 'p' => 63.5, 'ind' => 'Continuar suplementación con ácido fólico y ecografía morfométrica.', 'farma' => 6, 'dosis' => 'Loratadina 10 mg condicional si presenta rinitis gestacional.'],
            ['c_idx' => 10, 'diag' => 'Caries dental oclusal profunda en pieza 2.4', 'sint' => 'Sensibilidad dental al frío y calor sin compromiso pulpar.', 'pa' => '120/80', 't' => 36.7, 'p' => 81.0, 'ind' => 'Cepillado dental con dentífrico fluorado e hilo dental.', 'farma' => 5, 'dosis' => 'Diclofenaco 50 mg cada 12 horas en caso de molestia post operatoria.'],
            ['c_idx' => 11, 'diag' => 'Hipertensión arterial estadio 1 compensada', 'sint' => 'Control rutinario de cifras tensionales, sin síntomas agudos.', 'pa' => '135/85', 't' => 36.4, 'p' => 84.5, 'ind' => 'Dieta hiposódica y caminata diaria de 30 minutos.', 'farma' => 9, 'dosis' => 'Losartán Potásico 50 mg cada mañana.'],
            ['c_idx' => 12, 'diag' => 'Lumbalgia mecánica con contractura muscular', 'sint' => 'Dolor lumbar a la flexión tras cargar peso.', 'pa' => '125/80', 't' => 36.6, 'p' => 86.0, 'ind' => 'Calor seco local y faja de soporte durante actividad.', 'farma' => 5, 'dosis' => 'Diclofenaco 50 mg cada 12 horas por 5 días.'],
            ['c_idx' => 13, 'diag' => 'Dermatitis atópica en placas pruriginosas', 'sint' => 'Placas eritematosas y prurito intenso en flexuras de codos.', 'pa' => '118/78', 't' => 36.5, 'p' => 58.0, 'ind' => 'Crema emoliente hipoalergénica dos veces al día.', 'farma' => 6, 'dosis' => 'Loratadina 10 mg una vez al día por 10 días.'],
            ['c_idx' => 14, 'diag' => 'Cefalea tipo tensión recurrente', 'sint' => 'Pesadez cervical y dolor bilateral holocraneano.', 'pa' => '120/80', 't' => 36.6, 'p' => 72.0, 'ind' => 'Técnicas de manejo de estrés y corrección ergonómica.', 'farma' => 0, 'dosis' => 'Paracetamol 500 mg cada 8 horas ante episodios de dolor.'],
        ];

        foreach ($consultasData as $idx => $cd) {
            $c = $citaModels[$cd['c_idx']];

            $consulta = Consulta::updateOrCreate(
                ['cita_id' => $c->id],
                [
                    'hora_inicio_real' => $c->hora_inicio,
                    'hora_fin_real' => $c->hora_fin,
                    'sintomas' => $cd['sint'],
                    'diagnostico' => $cd['diag'],
                    'observaciones' => 'Examen físico segmentario completado sin hallazgos de alarma.',
                    'peso' => $cd['p'],
                    'presion_arterial' => $cd['pa'],
                    'temperatura' => $cd['t'],
                    'indicaciones' => $cd['ind'],
                ]
            );

            $receta = Receta::updateOrCreate(
                ['consulta_id' => $consulta->id],
                [
                    'codigo' => 'RX-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
                    'fecha_emision' => $c->fecha,
                    'observaciones' => 'Tratamiento ambulatorio dispensado bajo supervisión médica.',
                ]
            );

            $medF = $medFarmaModels[$cd['farma']];
            DetalleReceta::updateOrCreate(
                ['receta_id' => $receta->id, 'medicamento_id' => $medF->id],
                [
                    'dosis' => $medF->concentracion ?? '1 comprimido',
                    'frecuencia' => $cd['dosis'],
                    'duracion_tratamiento' => '3 a 7 días',
                    'cantidad' => 10,
                    'indicaciones' => 'Ingerir con abundante agua según horario establecido.',
                ]
            );
        }

        // =====================================================================
        // 13. RECORDATORIOS DE CITAS (10 registros - CU31)
        // =====================================================================
        foreach ($citaModels as $idx => $c) {
            if ($idx >= 10) break;
            Recordatorio::updateOrCreate(
                ['cita_id' => $c->id, 'canal' => ($idx % 2 === 0 ? 'Email' : 'WhatsApp')],
                [
                    'destinatario' => $c->paciente->persona->correo ?? 'paciente@gmail.com',
                    'mensaje' => "Recordatorio: Tiene cita {$c->codigo} con {$c->medico->persona->nombre_completo} el {$c->fecha} a las {$c->hora_inicio}.",
                    'estado' => ($idx < 5 ? 'Enviado' : 'Pendiente'),
                    'fecha_programada' => now()->subHours(24 - $idx),
                    'fecha_envio' => ($idx < 5 ? now()->subHours(20 - $idx) : null),
                ]
            );
        }

        // =====================================================================
        // 14. BITÁCORA DE AUDITORÍA (15 Registros - CU05 / RN15)
        // =====================================================================
        $bitacoraAcciones = [
            ['acc' => 'LOGIN', 'tab' => 'users', 'det' => 'Inicio de sesión exitoso del usuario admin'],
            ['acc' => 'CREAR_USUARIO', 'tab' => 'users', 'det' => 'Alta de cuenta médica para Dr. Alejandro Roca'],
            ['acc' => 'REGISTRAR_PACIENTE', 'tab' => 'pacientes', 'det' => 'Expediente clínico creado para Juan Mamani Condori'],
            ['acc' => 'PROGRAMAR_CITA', 'tab' => 'citas', 'det' => 'Cita CM-0001 programada para Medicina General'],
            ['acc' => 'CONFIRMAR_CITA', 'tab' => 'citas', 'det' => 'Asistencia confirmada para cita CM-0002'],
            ['acc' => 'ATENDER_CITA', 'tab' => 'citas', 'det' => 'Atención completada y cita CM-0006 marcada como Atendida'],
            ['acc' => 'REGISTRAR_CONSULTA', 'tab' => 'consultas', 'det' => 'Consulta clínica registrada con diagnóstico cefalea'],
            ['acc' => 'EMITIR_RECETA', 'tab' => 'recetas', 'det' => 'Receta RX-0001 emitida con prescripción de analgésicos'],
            ['acc' => 'REGISTRAR_ALERGIA', 'tab' => 'paciente_alergia', 'det' => 'Alergia severa a Penicilina registrada para Lucía Fernández'],
            ['acc' => 'BLOQUEAR_AGENDA', 'tab' => 'bloqueos_agenda', 'det' => 'Agenda médica bloqueada por capacitación científica'],
            ['acc' => 'CANCELAR_CITA', 'tab' => 'citas', 'det' => 'Cita CM-0016 cancelada a solicitud del paciente por viaje'],
            ['acc' => 'REGISTRAR_INASISTENCIA', 'tab' => 'citas', 'det' => 'Inasistencia marcada para cita CM-0019 por No-Show'],
            ['acc' => 'ENVIAR_RECORDATORIO', 'tab' => 'recordatorios', 'det' => 'Notificación por correo despachada exitosamente'],
            ['acc' => 'EXPORTAR_REPORTE', 'tab' => 'reportes', 'det' => 'Reporte consolidado de citas exportado a PDF y CSV'],
            ['acc' => 'ACTUALIZAR_PERMISO', 'tab' => 'roles', 'det' => 'Matriz RBAC de permisos actualizada para rol Médico'],
        ];

        foreach ($bitacoraAcciones as $idx => $ba) {
            Bitacora::create([
                'usuario_id' => $userAdmin->id,
                'accion' => $ba['acc'],
                'tabla_afectada' => $ba['tab'],
                'registro_id' => $idx + 1,
                'detalles' => $ba['det'],
                'ip_origen' => '18.218.0.2',
                'canal' => 'web',
                'fecha_hora' => now()->subHours(15 - $idx),
            ]);
        }
    }
}
