<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ciclo 2 - Fase 0 (Word Cap. 6.2.3.3: Funciones, procedimientos y disparadores)
 *
 * Reglas de negocio críticas reforzadas en PostgreSQL:
 *  1. fn_validar_ficha_libre       + trg_validar_ficha_libre       (RN07, RN09, RN29)
 *  2. fn_validar_transicion_estado + trg_validar_transicion_estado (RN18, RN20)
 *  3. fn_generar_fichas            + trg_generar_fichas            (RN29)
 *  4. fn_validar_consulta          + trg_validar_consulta          (RN22)
 *  5. sp_cancelar_cita (procedimiento almacenado)                  (RF09, RN19, RN21)
 *  6. fn_paciente_alergico (función de consulta)                   (RN24, CU26)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return; // Las funciones PL/pgSQL solo aplican a PostgreSQL
        }

        // 1. Impide dos citas activas en la misma ficha y fecha.
        //    Las citas Canceladas o en Inasistencia no ocupan la ficha.
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_validar_ficha_libre()
RETURNS TRIGGER AS $$
BEGIN
    IF EXISTS (
        SELECT 1
          FROM citas c
          JOIN estados_cita e ON e.id = c.estado_id
         WHERE c.ficha_id = NEW.ficha_id
           AND c.fecha = NEW.fecha
           AND c.id <> COALESCE(NEW.id, 0)
           AND e.nombre NOT IN ('Cancelada', 'Inasistencia')
    ) THEN
        RAISE EXCEPTION 'La ficha % ya está ocupada el %', NEW.ficha_id, NEW.fecha
            USING ERRCODE = 'unique_violation';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validar_ficha_libre ON citas;
CREATE TRIGGER trg_validar_ficha_libre
BEFORE INSERT OR UPDATE OF ficha_id, fecha ON citas
FOR EACH ROW EXECUTE FUNCTION fn_validar_ficha_libre();
SQL);

        // 2. Valida las transiciones de estado de la cita.
        //    Atendida, Cancelada e Inasistencia son estados finales.
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_validar_transicion_estado()
RETURNS TRIGGER AS $$
DECLARE
    v_actual VARCHAR(50);
    v_nuevo  VARCHAR(50);
BEGIN
    IF NEW.estado_id = OLD.estado_id THEN
        RETURN NEW;
    END IF;
    SELECT nombre INTO v_actual FROM estados_cita WHERE id = OLD.estado_id;
    SELECT nombre INTO v_nuevo  FROM estados_cita WHERE id = NEW.estado_id;
    IF v_actual IN ('Atendida', 'Cancelada', 'Inasistencia') THEN
        RAISE EXCEPTION 'La cita está en estado final (%) y no puede cambiar a %', v_actual, v_nuevo;
    END IF;
    IF v_actual = 'Confirmada' AND v_nuevo = 'Programada' THEN
        RAISE EXCEPTION 'Transición no válida: Confirmada -> Programada';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validar_transicion_estado ON citas;
CREATE TRIGGER trg_validar_transicion_estado
BEFORE UPDATE OF estado_id ON citas
FOR EACH ROW EXECUTE FUNCTION fn_validar_transicion_estado();
SQL);

        // 3. Genera las fichas de un horario: (hora_fin - hora_inicio) / duración.
        //    Es idempotente (ON CONFLICT) y convive con la generación en PHP (updateOrCreate).
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_generar_fichas()
RETURNS TRIGGER AS $$
DECLARE
    v_inicio TIME := NEW.hora_inicio;
    v_num    INTEGER := 1;
    v_dur    INTERVAL := make_interval(mins => COALESCE(NEW.duracion_cita_min, 30));
BEGIN
    IF TG_OP = 'UPDATE' THEN
        DELETE FROM fichas f
         WHERE f.horario_id = NEW.id
           AND NOT EXISTS (SELECT 1 FROM citas c WHERE c.ficha_id = f.id);
    END IF;
    WHILE v_inicio + v_dur <= NEW.hora_fin AND v_inicio + v_dur > v_inicio LOOP
        INSERT INTO fichas (horario_id, numero, hora_inicio, hora_fin, estado, created_at, updated_at)
        VALUES (NEW.id, v_num, v_inicio, v_inicio + v_dur, TRUE, NOW(), NOW())
        ON CONFLICT (horario_id, numero) DO NOTHING;
        v_inicio := v_inicio + v_dur;
        v_num := v_num + 1;
    END LOOP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_generar_fichas ON horarios_atencion;
CREATE TRIGGER trg_generar_fichas
AFTER INSERT OR UPDATE OF hora_inicio, hora_fin, duracion_cita_min ON horarios_atencion
FOR EACH ROW EXECUTE FUNCTION fn_generar_fichas();
SQL);

        // 4. Solo se registra la consulta de una cita que no esté Cancelada ni en Inasistencia.
        //    (La unicidad de una consulta por cita la garantiza UNIQUE(cita_id)).
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_validar_consulta()
RETURNS TRIGGER AS $$
DECLARE
    v_estado VARCHAR(50);
BEGIN
    SELECT e.nombre INTO v_estado
      FROM citas c JOIN estados_cita e ON e.id = c.estado_id
     WHERE c.id = NEW.cita_id;
    IF v_estado IN ('Cancelada', 'Inasistencia') THEN
        RAISE EXCEPTION 'No se puede registrar la consulta de una cita %', v_estado;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validar_consulta ON consultas;
CREATE TRIGGER trg_validar_consulta
BEFORE INSERT ON consultas
FOR EACH ROW EXECUTE FUNCTION fn_validar_consulta();
SQL);

        // 5. Procedimiento: cancela una cita registrando motivo, historial y bitácora.
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE PROCEDURE sp_cancelar_cita(p_cita_id BIGINT, p_usuario_id BIGINT, p_motivo TEXT)
LANGUAGE plpgsql AS $$
DECLARE
    v_anterior  BIGINT;
    v_cancelada BIGINT;
    v_codigo    VARCHAR(30);
BEGIN
    SELECT estado_id, codigo INTO v_anterior, v_codigo FROM citas WHERE id = p_cita_id FOR UPDATE;
    IF NOT FOUND THEN
        RAISE EXCEPTION 'La cita % no existe', p_cita_id;
    END IF;
    SELECT id INTO v_cancelada FROM estados_cita WHERE nombre = 'Cancelada';

    UPDATE citas
       SET estado_id = v_cancelada, motivo_cancelacion = p_motivo, updated_at = NOW()
     WHERE id = p_cita_id;

    INSERT INTO historial_estados_cita (cita_id, estado_anterior_id, estado_nuevo_id, usuario_id, fecha_hora, motivo, created_at, updated_at)
    VALUES (p_cita_id, v_anterior, v_cancelada, p_usuario_id, NOW(), p_motivo, NOW(), NOW());

    INSERT INTO bitacoras (usuario_id, accion, tabla_afectada, registro_id, detalles, canal, fecha_hora, created_at, updated_at)
    VALUES (p_usuario_id, 'CANCELAR_CITA', 'citas', p_cita_id,
            'Cita ' || v_codigo || ' cancelada. Motivo: ' || p_motivo, 'WEB', NOW(), NOW(), NOW());
END;
$$;
SQL);

        // 6. Función: alergias del paciente que coinciden con el medicamento a recetar.
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION fn_paciente_alergico(p_paciente_id BIGINT, p_medicamento_id BIGINT)
RETURNS TABLE (alergia VARCHAR, severidad VARCHAR, reaccion VARCHAR) AS $$
    SELECT a.nombre::VARCHAR, pa.severidad::VARCHAR, pa.reaccion::VARCHAR
      FROM paciente_alergia pa
      JOIN alergias a ON a.id = pa.alergia_id
      JOIN medicamentos m ON m.id = p_medicamento_id
     WHERE pa.paciente_id = p_paciente_id
       AND (LOWER(m.principio_activo) LIKE '%' || LOWER(a.nombre) || '%'
            OR LOWER(m.nombre_comercial) LIKE '%' || LOWER(a.nombre) || '%'
            OR LOWER(a.nombre) LIKE '%' || LOWER(m.principio_activo) || '%');
$$ LANGUAGE sql STABLE;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
DROP TRIGGER IF EXISTS trg_validar_ficha_libre ON citas;
DROP TRIGGER IF EXISTS trg_validar_transicion_estado ON citas;
DROP TRIGGER IF EXISTS trg_generar_fichas ON horarios_atencion;
DROP TRIGGER IF EXISTS trg_validar_consulta ON consultas;
DROP FUNCTION IF EXISTS fn_validar_ficha_libre();
DROP FUNCTION IF EXISTS fn_validar_transicion_estado();
DROP FUNCTION IF EXISTS fn_generar_fichas();
DROP FUNCTION IF EXISTS fn_validar_consulta();
DROP PROCEDURE IF EXISTS sp_cancelar_cita(BIGINT, BIGINT, TEXT);
DROP FUNCTION IF EXISTS fn_paciente_alergico(BIGINT, BIGINT);
SQL);
    }
};
