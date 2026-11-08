-- =============================================================================
-- UNIVERSIDAD AUTÓNOMA GABRIEL RENÉ MORENO (UAGRM)
-- FACULTAD DE INGENIERÍA EN CIENCIAS DE LA COMPUTACIÓN Y TELECOMUNICACIONES
-- MATERIA: SISTEMAS DE INFORMACIÓN 1 (SI1) - DOCENTE: M.Sc. Ing. Angélica Garzón Cuéllar
-- PROYECTO: Sistema Web para la Gestión de Citas Médicas "Clínica Vida Salud"
-- INTEGRANTE: Jhon Jairo Patzi Balvin
-- MOTOR: PostgreSQL 16+
-- =============================================================================
-- ESQUEMA DEFINITIVO OFICIAL: 25 TABLAS RELACIONALES, PROCEDIMIENTOS Y TRIGGERS
-- Coincide al 100% con las migraciones de Laravel y el Capítulo 6 del documento.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- LIMPIEZA DE TABLAS PREVIAS (ORDEN DE DEPENDENCIAS INVERSO)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS antecedentes CASCADE;
DROP TABLE IF EXISTS paciente_medicamento CASCADE;
DROP TABLE IF EXISTS paciente_alergia CASCADE;
DROP TABLE IF EXISTS alergias CASCADE;
DROP TABLE IF EXISTS detalle_recetas CASCADE;
DROP TABLE IF EXISTS recetas CASCADE;
DROP TABLE IF EXISTS medicamentos CASCADE;
DROP TABLE IF EXISTS consultas CASCADE;
DROP TABLE IF EXISTS recordatorios CASCADE;
DROP TABLE IF EXISTS bloqueos_agenda CASCADE;
DROP TABLE IF EXISTS historial_estados_cita CASCADE;
DROP TABLE IF EXISTS citas CASCADE;
DROP TABLE IF EXISTS estados_cita CASCADE;
DROP TABLE IF EXISTS fichas CASCADE;
DROP TABLE IF EXISTS horarios_atencion CASCADE;
DROP TABLE IF EXISTS medico_especialidad CASCADE;
DROP TABLE IF EXISTS medicos CASCADE;
DROP TABLE IF EXISTS especialidades CASCADE;
DROP TABLE IF EXISTS pacientes CASCADE;
DROP TABLE IF EXISTS bitacoras CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS rol_permiso CASCADE;
DROP TABLE IF EXISTS permisos CASCADE;
DROP TABLE IF EXISTS roles CASCADE;
DROP TABLE IF EXISTS personas CASCADE;

-- =============================================================================
-- 1. PAQUETE P1: SEGURIDAD, ACCESO Y USUARIOS
-- =============================================================================

-- Tabla: PERSONAS (Clase base generalizadora)
CREATE TABLE personas (
    id BIGSERIAL PRIMARY KEY,
    ci VARCHAR(20) NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    correo VARCHAR(150),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_personas_ci UNIQUE (ci)
);

-- Tabla: ROLES
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_roles_nombre UNIQUE (nombre)
);

-- Tabla: PERMISOS (Identificados por código único alfanumérico)
CREATE TABLE permisos (
    id BIGSERIAL PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_permisos_codigo UNIQUE (codigo)
);

-- Tabla: ROL_PERMISO (Tabla asociativa de matriz de privilegios)
CREATE TABLE rol_permiso (
    id BIGSERIAL PRIMARY KEY,
    rol_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permiso_id BIGINT NOT NULL REFERENCES permisos(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_rol_permiso UNIQUE (rol_id, permiso_id)
);

-- Tabla: USERS (Cuentas de autenticación en el sistema)
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    persona_id BIGINT NOT NULL REFERENCES personas(id) ON DELETE RESTRICT,
    rol_id BIGINT NOT NULL REFERENCES roles(id) ON DELETE RESTRICT,
    name VARCHAR(150) NOT NULL,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    ultimo_acceso TIMESTAMP WITHOUT TIME ZONE,
    remember_token VARCHAR(100),
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_persona_id UNIQUE (persona_id),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email)
);

-- Tabla: BITACORAS (Auditoría inmutable de transacciones críticas - RN15)
CREATE TABLE bitacoras (
    id BIGSERIAL PRIMARY KEY,
    usuario_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    registro_id BIGINT,
    detalles TEXT,
    ip_origen VARCHAR(45),
    canal VARCHAR(20) NOT NULL DEFAULT 'web',
    fecha_hora TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- 2. PAQUETE P2: GESTIÓN DE PACIENTES
-- =============================================================================

-- Tabla: PACIENTES (Expedientes demográficos y clínicos)
CREATE TABLE pacientes (
    id BIGSERIAL PRIMARY KEY,
    persona_id BIGINT NOT NULL REFERENCES personas(id) ON DELETE CASCADE,
    codigo VARCHAR(30) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    sexo VARCHAR(10) NOT NULL DEFAULT 'M' CHECK (sexo IN ('M', 'F', 'Otro')),
    direccion VARCHAR(255),
    tipo_sangre VARCHAR(10),
    otras_alergias TEXT,
    contacto_emerg_nombre VARCHAR(150),
    contacto_emerg_parentesco VARCHAR(50),
    contacto_emerg_telefono VARCHAR(20),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_pacientes_persona_id UNIQUE (persona_id),
    CONSTRAINT uq_pacientes_codigo UNIQUE (codigo)
);

-- =============================================================================
-- 3. PAQUETE P3: GESTIÓN MÉDICA, ESPECIALIDADES Y HORARIOS
-- =============================================================================

-- Tabla: ESPECIALIDADES
CREATE TABLE especialidades (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_especialidades_nombre UNIQUE (nombre)
);

-- Tabla: MEDICOS
CREATE TABLE medicos (
    id BIGSERIAL PRIMARY KEY,
    persona_id BIGINT NOT NULL REFERENCES personas(id) ON DELETE CASCADE,
    codigo VARCHAR(30) NOT NULL,
    matricula_profesional VARCHAR(50) NOT NULL,
    biografia TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_medicos_persona_id UNIQUE (persona_id),
    CONSTRAINT uq_medicos_codigo UNIQUE (codigo),
    CONSTRAINT uq_medicos_matricula UNIQUE (matricula_profesional)
);

-- Tabla: MEDICO_ESPECIALIDAD (Relación N:M)
CREATE TABLE medico_especialidad (
    id BIGSERIAL PRIMARY KEY,
    medico_id BIGINT NOT NULL REFERENCES medicos(id) ON DELETE CASCADE,
    especialidad_id BIGINT NOT NULL REFERENCES especialidades(id) ON DELETE CASCADE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_medico_especialidad UNIQUE (medico_id, especialidad_id)
);

-- Tabla: HORARIOS_ATENCION (Jornadas laborales semanales)
CREATE TABLE horarios_atencion (
    id BIGSERIAL PRIMARY KEY,
    medico_id BIGINT NOT NULL REFERENCES medicos(id) ON DELETE CASCADE,
    dia_semana VARCHAR(15) NOT NULL CHECK (dia_semana IN ('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo')),
    hora_inicio TIME WITHOUT TIME ZONE NOT NULL,
    hora_fin TIME WITHOUT TIME ZONE NOT NULL,
    duracion_cita_min INTEGER NOT NULL DEFAULT 30,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_horario_horas CHECK (hora_fin > hora_inicio)
);

-- Tabla: FICHAS (Turnos de atención generados por jornada - RN29)
CREATE TABLE fichas (
    id BIGSERIAL PRIMARY KEY,
    horario_id BIGINT NOT NULL REFERENCES horarios_atencion(id) ON DELETE CASCADE,
    numero INTEGER NOT NULL,
    hora_inicio TIME WITHOUT TIME ZONE NOT NULL,
    hora_fin TIME WITHOUT TIME ZONE NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_fichas_horario_numero UNIQUE (horario_id, numero)
);

-- =============================================================================
-- 4. PAQUETE P4 Y P5: CITAS MÉDICAS, ESTADOS Y AGENDA
-- =============================================================================

-- Tabla: ESTADOS_CITA (Catálogo oficial de ciclo de vida de la cita)
CREATE TABLE estados_cita (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255),
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_estados_cita_nombre UNIQUE (nombre)
);

-- Tabla: CITAS (Transacción central de reserva)
CREATE TABLE citas (
    id BIGSERIAL PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL,
    paciente_id BIGINT NOT NULL REFERENCES pacientes(id) ON DELETE RESTRICT,
    medico_id BIGINT NOT NULL REFERENCES medicos(id) ON DELETE RESTRICT,
    especialidad_id BIGINT NOT NULL REFERENCES especialidades(id) ON DELETE RESTRICT,
    ficha_id BIGINT NOT NULL REFERENCES fichas(id) ON DELETE RESTRICT,
    estado_id BIGINT NOT NULL REFERENCES estados_cita(id) ON DELETE RESTRICT,
    usuario_registro_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    fecha DATE NOT NULL,
    hora_inicio TIME WITHOUT TIME ZONE NOT NULL,
    hora_fin TIME WITHOUT TIME ZONE NOT NULL,
    motivo TEXT,
    modalidad VARCHAR(20) NOT NULL DEFAULT 'Presencial' CHECK (modalidad IN ('Presencial', 'Virtual')),
    motivo_cancelacion TEXT,
    fecha_creacion TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_citas_codigo UNIQUE (codigo)
);

-- Tabla: HISTORIAL_ESTADOS_CITA (Auditoría cronológica inmutable de transiciones)
CREATE TABLE historial_estados_cita (
    id BIGSERIAL PRIMARY KEY,
    cita_id BIGINT NOT NULL REFERENCES citas(id) ON DELETE CASCADE,
    estado_anterior_id BIGINT REFERENCES estados_cita(id) ON DELETE RESTRICT,
    estado_nuevo_id BIGINT NOT NULL REFERENCES estados_cita(id) ON DELETE RESTRICT,
    usuario_id BIGINT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    fecha_hora TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: BLOQUEOS_AGENDA (CU30: Bloqueo de turnos por vacaciones, permisos o feriados)
CREATE TABLE bloqueos_agenda (
    id BIGSERIAL PRIMARY KEY,
    medico_id BIGINT NOT NULL REFERENCES medicos(id) ON DELETE CASCADE,
    tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('Vacaciones', 'Permiso', 'Capacitación', 'Feriado', 'Otro')),
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    hora_inicio TIME WITHOUT TIME ZONE,
    hora_fin TIME WITHOUT TIME ZONE,
    dia_completo BOOLEAN NOT NULL DEFAULT TRUE,
    motivo TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_bloqueo_fechas CHECK (fecha_fin >= fecha_inicio)
);

-- Tabla: RECORDATORIOS (CU31: Notificaciones automáticas programadas de citas)
CREATE TABLE recordatorios (
    id BIGSERIAL PRIMARY KEY,
    cita_id BIGINT NOT NULL REFERENCES citas(id) ON DELETE CASCADE,
    canal VARCHAR(20) NOT NULL CHECK (canal IN ('Email', 'WhatsApp', 'SMS')),
    destinatario VARCHAR(150) NOT NULL,
    mensaje TEXT NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente' CHECK (estado IN ('Pendiente', 'Enviado', 'Fallido')),
    fecha_programada TIMESTAMP WITHOUT TIME ZONE NOT NULL,
    fecha_envio TIMESTAMP WITHOUT TIME ZONE,
    error TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- 5. PAQUETE P7: ATENCIÓN MÉDICA, CONSULTAS Y RECETAS
-- =============================================================================

-- Tabla: CONSULTAS (Registro clínico de la atención médica - CU23)
CREATE TABLE consultas (
    id BIGSERIAL PRIMARY KEY,
    cita_id BIGINT NOT NULL REFERENCES citas(id) ON DELETE CASCADE,
    hora_inicio_real TIME WITHOUT TIME ZONE NOT NULL,
    hora_fin_real TIME WITHOUT TIME ZONE,
    motivo_consulta TEXT NOT NULL,
    sintomas TEXT,
    presion_arterial VARCHAR(20),
    temperatura NUMERIC(4,1),
    peso_kg NUMERIC(5,2),
    altura_cm NUMERIC(5,2),
    diagnostico TEXT NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_consultas_cita_id UNIQUE (cita_id)
);

-- Tabla: MEDICAMENTOS (Catálogo farmacéutico)
CREATE TABLE medicamentos (
    id BIGSERIAL PRIMARY KEY,
    nombre_comercial VARCHAR(150) NOT NULL,
    principio_activo VARCHAR(150) NOT NULL,
    concentracion VARCHAR(100),
    presentacion VARCHAR(100),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: RECETAS (Prescripción médica emitida en la consulta - CU25)
CREATE TABLE recetas (
    id BIGSERIAL PRIMARY KEY,
    consulta_id BIGINT NOT NULL REFERENCES consultas(id) ON DELETE CASCADE,
    codigo VARCHAR(30) NOT NULL,
    indicaciones_generales TEXT,
    fecha_emision DATE NOT NULL,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_recetas_consulta_id UNIQUE (consulta_id),
    CONSTRAINT uq_recetas_codigo UNIQUE (codigo)
);

-- Tabla: DETALLE_RECETAS (Detalle de fármacos dosificados)
CREATE TABLE detalle_recetas (
    id BIGSERIAL PRIMARY KEY,
    receta_id BIGINT NOT NULL REFERENCES recetas(id) ON DELETE CASCADE,
    medicamento_id BIGINT NOT NULL REFERENCES medicamentos(id) ON DELETE RESTRICT,
    dosis VARCHAR(100) NOT NULL,
    frecuencia VARCHAR(100) NOT NULL,
    duracion_dias INTEGER NOT NULL DEFAULT 1,
    cantidad INTEGER NOT NULL DEFAULT 1,
    indicaciones_especificas TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- 6. HISTORIA CLÍNICA, ALERGIAS Y ANTECEDENTES
-- =============================================================================

-- Tabla: ALERGIAS (Catálogo de sustancias alérgenas)
CREATE TABLE alergias (
    id BIGSERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('Medicamento', 'Alimento', 'Ambiental', 'Otro')),
    descripcion TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_alergias_nombre UNIQUE (nombre)
);

-- Tabla: PACIENTE_ALERGIA (Alergias diagnosticadas al paciente - CU26)
CREATE TABLE paciente_alergia (
    id BIGSERIAL PRIMARY KEY,
    paciente_id BIGINT NOT NULL REFERENCES pacientes(id) ON DELETE CASCADE,
    alergia_id BIGINT NOT NULL REFERENCES alergias(id) ON DELETE CASCADE,
    severidad VARCHAR(20) NOT NULL CHECK (severidad IN ('Leve', 'Moderada', 'Grave')),
    reaccion TEXT,
    fecha_deteccion DATE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_paciente_alergia UNIQUE (paciente_id, alergia_id)
);

-- Tabla: PACIENTE_MEDICAMENTO (Tratamientos habituales y contraindicados - CU27)
CREATE TABLE paciente_medicamento (
    id BIGSERIAL PRIMARY KEY,
    paciente_id BIGINT NOT NULL REFERENCES pacientes(id) ON DELETE CASCADE,
    medicamento_id BIGINT NOT NULL REFERENCES medicamentos(id) ON DELETE RESTRICT,
    tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('Habitual', 'Contraindicado', 'Alergia conocida')),
    observacion TEXT,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Tabla: ANTECEDENTES (Antecedentes patológicos, quirúrgicos y familiares - CU28)
CREATE TABLE antecedentes (
    id BIGSERIAL PRIMARY KEY,
    paciente_id BIGINT NOT NULL REFERENCES pacientes(id) ON DELETE CASCADE,
    tipo VARCHAR(50) NOT NULL CHECK (tipo IN ('Patológico', 'Quirúrgico', 'Familiar', 'Tóxico', 'Alergias')),
    descripcion TEXT NOT NULL,
    fecha_diagnostico DATE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- ÍNDICES DE RENDIMIENTO PARA CONSULTAS Y AGENDA
-- =============================================================================
CREATE INDEX idx_citas_medico_fecha ON citas (medico_id, fecha);
CREATE INDEX idx_citas_ficha_fecha ON citas (ficha_id, fecha);
CREATE INDEX idx_citas_paciente ON citas (paciente_id);
CREATE INDEX idx_medicamentos_principio ON medicamentos (principio_activo);
CREATE INDEX idx_bitacoras_fecha ON bitacoras (fecha_hora);

-- =============================================================================
-- FUNCIONES, PROCEDIMIENTOS Y DISPARADORES EN POSTGRESQL (CAPÍTULO 6.2.3.3)
-- =============================================================================

-- 1. Anti-solapamiento de citas en base de datos (RN07, RN09, RN29)
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

-- 2. Validación de transiciones de estados de citas (RN18, RN20)
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

-- 3. Generación automática de fichas al insertar o actualizar horarios (RN29)
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

-- 4. Validación de cita apta para registrar consulta (RN22)
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

-- 5. Procedimiento almacenado para cancelación transaccional de citas (RF09, RN19, RN21)
CREATE OR REPLACE PROCEDURE sp_cancelar_cita(p_cita_id BIGINT, p_usuario_id BIGINT, p_motivo TEXT)
LANGUAGE plpgsql AS $$
DECLARE
    v_anterior BIGINT;
    v_cancelada BIGINT;
BEGIN
    SELECT estado_id INTO v_anterior FROM citas WHERE id = p_cita_id FOR UPDATE;
    SELECT id INTO v_cancelada FROM estados_cita WHERE nombre = 'Cancelada';
    UPDATE citas
       SET estado_id = v_cancelada, motivo_cancelacion = p_motivo, updated_at = NOW()
     WHERE id = p_cita_id;
    INSERT INTO historial_estados_cita (cita_id, estado_anterior_id, estado_nuevo_id, usuario_id, fecha_hora, motivo, created_at, updated_at)
    VALUES (p_cita_id, v_anterior, v_cancelada, p_usuario_id, NOW(), p_motivo, NOW(), NOW());
    INSERT INTO bitacoras (usuario_id, accion, tabla_afectada, registro_id, detalles, canal, fecha_hora, created_at, updated_at)
    VALUES (p_usuario_id, 'CANCELAR_CITA', 'citas', p_cita_id, p_motivo, 'web', NOW(), NOW(), NOW());
END;
$$;

-- 6. Función para verificar alergias a medicamentos (RN24, CU26)
CREATE OR REPLACE FUNCTION fn_paciente_alergico(p_paciente_id BIGINT, p_medicamento_id BIGINT)
RETURNS TABLE (alergia VARCHAR, severidad VARCHAR, reaccion VARCHAR) AS $$
    SELECT a.nombre, pa.severidad, pa.reaccion
      FROM paciente_alergia pa
      JOIN alergias a ON a.id = pa.alergia_id
      JOIN medicamentos m ON m.id = p_medicamento_id
     WHERE pa.paciente_id = p_paciente_id
       AND (LOWER(m.principio_activo) LIKE '%' || LOWER(a.nombre) || '%'
            OR LOWER(m.nombre_comercial) LIKE '%' || LOWER(a.nombre) || '%');
$$ LANGUAGE sql;

-- =============================================================================
-- DATOS INICIALES SEMILLA (ROLES, ESTADOS Y USUARIO ADMINISTRADOR)
-- =============================================================================

INSERT INTO roles (nombre, descripcion) VALUES
('Administrador', 'Control total y configuración del sistema'),
('Recepcionista', 'Gestión de pacientes, citas y recepción de consultas'),
('Medico', 'Gestión clínica, atención médica y recetas'),
('Paciente', 'Portal de autoservicio de citas e historial personal'),
('Auditor', 'Consulta y supervisión de bitácora y reportes');

INSERT INTO estados_cita (nombre, descripcion) VALUES
('Programada', 'Cita reservada pendiente de confirmación o atención'),
('Confirmada', 'Asistencia verificada por recepción o paciente'),
('Atendida', 'Consulta médica completada por el facultativo'),
('Cancelada', 'Cita anulada por paciente, médico o administración'),
('Inasistencia', 'Paciente no se presentó en la hora reservada');

INSERT INTO personas (ci, nombres, apellidos, telefono, correo) VALUES
('10000001', 'Administrador', 'Principal', '70000001', 'admin@vidasalud.bo');

INSERT INTO users (persona_id, rol_id, name, username, email, password) VALUES
(1, 1, 'Administrador del Sistema', 'admin', 'admin@vidasalud.bo', '$2y$12$6/tN0XmQn3t3L5V0X9ZveOTNq3m2e9Qv6W8a9pP9Z2w0s3x1k1n9K');
