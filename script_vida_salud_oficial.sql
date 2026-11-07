-- =============================================================================
-- UNIVERSIDAD AUTÓNOMA GABRIEL RENÉ MORENO (UAGRM)
-- FACULTAD DE INGENIERÍA EN CIENCIAS DE LA COMPUTACIÓN Y TELECOMUNICACIONES
-- MATERIA: SISTEMAS DE INFORMACIÓN 1 (SI1) - DOCENTE: M.Sc. Ing. Angélica Garzón Cuéllar
-- PROYECTO: Sistema Web para la Gestión de Citas Médicas "Clínica Vida Salud"
-- INTEGRANTE: Jhon Jairo Patzi Balvin
-- MOTOR: PostgreSQL 16+
-- =============================================================================

-- =============================================================================
-- 6.2.3.2. SCRIPT DE CREACIÓN DE BASE DE DATOS Y TABLAS (DDL)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. PAQUETE P1: SEGURIDAD, ACCESO Y USUARIOS
-- -----------------------------------------------------------------------------

-- Tabla: PERSONA (Clase base generalizadora)
CREATE TABLE "personas" (
    "id" BIGSERIAL PRIMARY KEY,
    "ci" VARCHAR(20) NOT NULL,
    "nombres" VARCHAR(100) NOT NULL,
    "apellidos" VARCHAR(100) NOT NULL,
    "telefono" VARCHAR(20),
    "correo" VARCHAR(150),
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_personas_ci" UNIQUE ("ci")
);

-- Tabla: ROL
CREATE TABLE "roles" (
    "id" BIGSERIAL PRIMARY KEY,
    "nombre" VARCHAR(50) NOT NULL,
    "descripcion" VARCHAR(255),
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_roles_nombre" UNIQUE ("nombre")
);

-- Tabla: PERMISO
CREATE TABLE "permisos" (
    "id" BIGSERIAL PRIMARY KEY,
    "nombre" VARCHAR(80) NOT NULL,
    "modulo" VARCHAR(50) NOT NULL,
    "descripcion" VARCHAR(255),
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_permisos_nombre" UNIQUE ("nombre")
);

-- Tabla Asociativa: ROL_PERMISO (N:M)
CREATE TABLE "rol_permiso" (
    "id" BIGSERIAL PRIMARY KEY,
    "rol_id" BIGINT NOT NULL REFERENCES "roles"("id") ON DELETE CASCADE,
    "permiso_id" BIGINT NOT NULL REFERENCES "permisos"("id") ON DELETE CASCADE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_rol_permiso" UNIQUE ("rol_id", "permiso_id")
);

-- Tabla: USUARIO (Cuenta de acceso vinculada a persona y rol)
CREATE TABLE "users" (
    "id" BIGSERIAL PRIMARY KEY,
    "persona_id" BIGINT NOT NULL REFERENCES "personas"("id") ON DELETE RESTRICT,
    "rol_id" BIGINT NOT NULL REFERENCES "roles"("id") ON DELETE RESTRICT,
    "name" VARCHAR(255) NOT NULL,
    "username" VARCHAR(50) NOT NULL,
    "email" VARCHAR(150) NOT NULL,
    "password" VARCHAR(255) NOT NULL,
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "ultimo_acceso" TIMESTAMP(0) WITHOUT TIME ZONE,
    "remember_token" VARCHAR(100),
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_users_username" UNIQUE ("username"),
    CONSTRAINT "uq_users_email" UNIQUE ("email")
);

-- Tabla: BITÁCORA (Auditoría inmutable del sistema - RN15 / CU05 / CU12)
CREATE TABLE "bitacoras" (
    "id" BIGSERIAL PRIMARY KEY,
    "usuario_id" BIGINT REFERENCES "users"("id") ON DELETE SET NULL,
    "accion" VARCHAR(80) NOT NULL,
    "tabla_afectada" VARCHAR(60) NOT NULL,
    "registro_id" BIGINT,
    "detalles" TEXT,
    "ip" VARCHAR(45) NOT NULL,
    "canal" VARCHAR(20) NOT NULL DEFAULT 'WEB',
    "fecha_hora" TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------------------------------
-- 2. PAQUETE P2: GESTIÓN DE PACIENTES
-- -----------------------------------------------------------------------------

-- Tabla: PACIENTE (Especialización 1:1 de Persona)
CREATE TABLE "pacientes" (
    "id" BIGSERIAL PRIMARY KEY,
    "persona_id" BIGINT NOT NULL REFERENCES "personas"("id") ON DELETE RESTRICT,
    "codigo" VARCHAR(20) NOT NULL,
    "fecha_nacimiento" DATE,
    "sexo" CHAR(1) CHECK ("sexo" IN ('M', 'F')),
    "direccion" VARCHAR(255),
    "tipo_sangre" VARCHAR(10),
    "alergias" TEXT,
    "contacto_emergencia" VARCHAR(150),
    "telefono_emergencia" VARCHAR(20),
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_pacientes_persona" UNIQUE ("persona_id"),
    CONSTRAINT "uq_pacientes_codigo" UNIQUE ("codigo")
);

-- -----------------------------------------------------------------------------
-- 3. PAQUETE P3: GESTIÓN MÉDICA, ESPECIALIDADES Y HORARIOS
-- -----------------------------------------------------------------------------

-- Tabla: ESPECIALIDAD
CREATE TABLE "especialidades" (
    "id" BIGSERIAL PRIMARY KEY,
    "nombre" VARCHAR(100) NOT NULL,
    "descripcion" TEXT,
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_especialidades_nombre" UNIQUE ("nombre")
);

-- Tabla: MÉDICO (Especialización 1:1 de Persona)
CREATE TABLE "medicos" (
    "id" BIGSERIAL PRIMARY KEY,
    "persona_id" BIGINT NOT NULL REFERENCES "personas"("id") ON DELETE RESTRICT,
    "codigo" VARCHAR(20) NOT NULL,
    "registro_profesional" VARCHAR(50) NOT NULL,
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_medicos_persona" UNIQUE ("persona_id"),
    CONSTRAINT "uq_medicos_codigo" UNIQUE ("codigo"),
    CONSTRAINT "uq_medicos_registro" UNIQUE ("registro_profesional")
);

-- Tabla Asociativa: MÉDICO_ESPECIALIDAD (N:M)
CREATE TABLE "medico_especialidad" (
    "id" BIGSERIAL PRIMARY KEY,
    "medico_id" BIGINT NOT NULL REFERENCES "medicos"("id") ON DELETE CASCADE,
    "especialidad_id" BIGINT NOT NULL REFERENCES "especialidades"("id") ON DELETE CASCADE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_medico_especialidad" UNIQUE ("medico_id", "especialidad_id")
);

-- Tabla: HORARIO_ATENCIÓN (Jornada semanal de atención del médico)
CREATE TABLE "horarios_atencion" (
    "id" BIGSERIAL PRIMARY KEY,
    "medico_id" BIGINT NOT NULL REFERENCES "medicos"("id") ON DELETE CASCADE,
    "dia_semana" VARCHAR(15) NOT NULL CHECK ("dia_semana" IN ('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo')),
    "hora_inicio" TIME(0) WITHOUT TIME ZONE NOT NULL,
    "hora_fin" TIME(0) WITHOUT TIME ZONE NOT NULL,
    "duracion_minutos" INTEGER NOT NULL DEFAULT 30,
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "chk_horario_horas" CHECK ("hora_fin" > "hora_inicio")
);

-- Tabla: FICHA (Bloques correlativos predeterminados de 30 min calculados)
CREATE TABLE "fichas" (
    "id" BIGSERIAL PRIMARY KEY,
    "horario_id" BIGINT NOT NULL REFERENCES "horarios_atencion"("id") ON DELETE CASCADE,
    "numero" INTEGER NOT NULL,
    "hora_inicio" TIME(0) WITHOUT TIME ZONE NOT NULL,
    "hora_fin" TIME(0) WITHOUT TIME ZONE NOT NULL,
    "estado" BOOLEAN NOT NULL DEFAULT TRUE,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_fichas_horario_numero" UNIQUE ("horario_id", "numero")
);

-- -----------------------------------------------------------------------------
-- 4. PAQUETE P4: NÚCLEO DE PROGRAMACIÓN DE CITAS MÉDICAS
-- -----------------------------------------------------------------------------

-- Tabla: ESTADO_CITA (Catálogo de ciclo de vida de la cita)
CREATE TABLE "estados_cita" (
    "id" BIGSERIAL PRIMARY KEY,
    "nombre" VARCHAR(30) NOT NULL,
    "descripcion" VARCHAR(150),
    "color" VARCHAR(10) DEFAULT '#0284c7',
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_estados_cita_nombre" UNIQUE ("nombre")
);

-- Tabla Transaccional: CITA MÉDICA
CREATE TABLE "citas" (
    "id" BIGSERIAL PRIMARY KEY,
    "codigo" VARCHAR(30) NOT NULL,
    "paciente_id" BIGINT NOT NULL REFERENCES "pacientes"("id") ON DELETE RESTRICT,
    "medico_id" BIGINT NOT NULL REFERENCES "medicos"("id") ON DELETE RESTRICT,
    "especialidad_id" BIGINT NOT NULL REFERENCES "especialidades"("id") ON DELETE RESTRICT,
    "ficha_id" BIGINT NOT NULL REFERENCES "fichas"("id") ON DELETE RESTRICT,
    "estado_id" BIGINT NOT NULL REFERENCES "estados_cita"("id") ON DELETE RESTRICT,
    "usuario_registro_id" BIGINT NOT NULL REFERENCES "users"("id") ON DELETE RESTRICT,
    "fecha" DATE NOT NULL,
    "hora_inicio" TIME(0) WITHOUT TIME ZONE NOT NULL,
    "hora_fin" TIME(0) WITHOUT TIME ZONE NOT NULL,
    "motivo" TEXT,
    "modalidad" VARCHAR(20) NOT NULL DEFAULT 'Presencial' CHECK ("modalidad" IN ('Presencial', 'Virtual')),
    "motivo_cancelacion" TEXT,
    "fecha_creacion" TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT "uq_citas_codigo" UNIQUE ("codigo")
);

-- Índices para optimización de consultas de agenda (RN07 / RN13)
CREATE INDEX "idx_citas_medico_fecha" ON "citas"("medico_id", "fecha");
CREATE INDEX "idx_citas_ficha_fecha" ON "citas"("ficha_id", "fecha");

-- Tabla: HISTORIAL_ESTADOS_CITA (Trazabilidad inmutable de transiciones)
CREATE TABLE "historial_estados_cita" (
    "id" BIGSERIAL PRIMARY KEY,
    "cita_id" BIGINT NOT NULL REFERENCES "citas"("id") ON DELETE CASCADE,
    "estado_anterior_id" BIGINT REFERENCES "estados_cita"("id") ON DELETE RESTRICT,
    "estado_nuevo_id" BIGINT NOT NULL REFERENCES "estados_cita"("id") ON DELETE RESTRICT,
    "usuario_id" BIGINT NOT NULL REFERENCES "users"("id") ON DELETE RESTRICT,
    "fecha_hora" TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "motivo" TEXT,
    "created_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);


-- =============================================================================
-- 6.2.3.3. PROCEDURES Y TRIGGERS (REGLAS DE NEGOCIO EN BASE DE DATOS)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- TRIGGER 1: AUDITORÍA AUTOMÁTICA EN BITÁCORA AL AGENDAR CITA (RN15 / CU15)
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_trg_auditar_cita()
RETURNS TRIGGER AS $$
BEGIN
    IF (TG_OP = 'INSERT') THEN
        INSERT INTO bitacoras (usuario_id, accion, tabla_afectada, registro_id, detalles, ip, canal, fecha_hora)
        VALUES (
            NEW.usuario_registro_id,
            'AGENDAR_CITA',
            'citas',
            NEW.id,
            'Cita médica ' || NEW.codigo || ' agendada para el ' || TO_CHAR(NEW.fecha, 'DD/MM/YYYY') || ' a las ' || TO_CHAR(NEW.hora_inicio, 'HH24:MI'),
            '127.0.0.1',
            'WEB',
            NOW()
        );
        RETURN NEW;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_auditar_cita ON citas;
CREATE TRIGGER trg_auditar_cita
AFTER INSERT ON citas
FOR EACH ROW
EXECUTE FUNCTION fn_trg_auditar_cita();


-- -----------------------------------------------------------------------------
-- TRIGGER 2: CONTROL DE PREVENCIÓN DE DUPLICIDAD Y SOLAPAMIENTO (RN07 / RN13)
-- Impide que dos citas ocupen la misma ficha y fecha si la cita previa está activa.
-- -----------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION fn_trg_validar_no_solapamiento()
RETURNS TRIGGER AS $$
DECLARE
    v_ocupadas INTEGER;
BEGIN
    SELECT COUNT(*)
    INTO v_ocupadas
    FROM citas
    WHERE medico_id = NEW.medico_id
      AND fecha = NEW.fecha
      AND ficha_id = NEW.ficha_id
      AND estado_id != (SELECT id FROM estados_cita WHERE nombre = 'Cancelada' LIMIT 1)
      AND id != COALESCE(NEW.id, 0);

    IF v_ocupadas > 0 THEN
        RAISE EXCEPTION 'Regla RN07/RN13: La ficha seleccionada ya se encuentra reservada para este médico en la fecha indicada.';
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_validar_no_solapamiento ON citas;
CREATE TRIGGER trg_validar_no_solapamiento
BEFORE INSERT OR UPDATE ON citas
FOR EACH ROW
EXECUTE FUNCTION fn_trg_validar_no_solapamiento();


-- =============================================================================
-- INSERCIONES DE DATOS INICIALES Y POBLACIÓN MASIVA (DML)
-- =============================================================================

-- 1. Roles del Sistema
INSERT INTO "roles" ("id", "nombre", "descripcion", "estado") VALUES
(1, 'Administrador', 'Control total de la clinica, usuarios, personal medico y reportes.', TRUE),
(2, 'Recepcionista', 'Gestion de pacientes, asignacion de turnos y programacion de citas.', TRUE),
(3, 'Medico', 'Acceso a expedientes clinicos y agenda diaria de atencion.', TRUE),
(4, 'Paciente', 'Consulta de citas programadas y portal de usuario.', TRUE);

-- 2. Estados de Cita
INSERT INTO "estados_cita" ("id", "nombre", "descripcion", "color") VALUES
(1, 'Programada', 'Cita reservada en espera de confirmacion o atencion.', '#0284c7'),
(2, 'Confirmada', 'Paciente confirmo su asistencia a la consulta medica.', '#1d4ed8'),
(3, 'Atendida', 'Consulta medica realizada satisfactoriamente por el facultativo.', '#15803d'),
(4, 'Cancelada', 'Cita suspendida; libera el cupo para otros pacientes.', '#b91c1c'),
(5, 'Inasistencia', 'El paciente no se presento en el horario correspondiente.', '#92400e');

-- 3. Especialidades Clínicas
INSERT INTO "especialidades" ("id", "nombre", "descripcion", "estado") VALUES
(1, 'Medicina General', 'Atencion integral primaria y valoracion de salud preventiva.', TRUE),
(2, 'Pediatria', 'Especialidad dedicada al cuidado de la salud infantil y adolescentes.', TRUE),
(3, 'Cardiologia', 'Diagnostico y tratamiento de trastornos cardiovasculares y presion arterial.', TRUE),
(4, 'Ginecologia y Obstetricia', 'Atencion de la salud femenina, control prenatal y reproductivo.', TRUE),
(5, 'Odontologia Integral', 'Salud bucodental, endodoncia, ortodoncia y cirugia maxilofacial.', TRUE),
(6, 'Dermatologia', 'Tratamiento de afecciones cutaneas, alergias y cuidado de la piel.', TRUE),
(7, 'Traumatologia y Ortopedia', 'Atencion de lesiones osteomusculares, fracturas y articulaciones.', TRUE),
(8, 'Oftalmologia', 'Cuidado y cirugia de la vision, salud ocular y refraccion.', TRUE);

-- 4. Persona y Usuario Administrador (Titular del Proyecto)
INSERT INTO "personas" ("id", "ci", "nombres", "apellidos", "telefono", "correo", "estado") VALUES
(1, '11325356', 'Jhon Jairo', 'Patzi Balvin', '67891791', '47896jhonpro@gmail.com', TRUE);

INSERT INTO "users" ("id", "persona_id", "rol_id", "name", "username", "email", "password", "estado") VALUES
(1, 1, 1, 'Jhon Jairo Patzi Balvin', 'admin', '47896jhonpro@gmail.com', '$2y$12$6yO2v4y9hNsqzZkpJ/YqQeXgVvY2e8hS0lG8qA5zQ.p1r9YlqQYv2', TRUE);

-- 5. Personal Médico (8 Facultativos con Especialidades)
INSERT INTO "personas" ("id", "ci", "nombres", "apellidos", "telefono", "correo", "estado") VALUES
(2, '4892011', 'Carlos Alberto', 'Fernandez Rios', '71023456', 'cfernandez@vidasalud.com', TRUE),
(3, '5120399', 'Mariana Patricia', 'Suarez Mendez', '72134567', 'msuarez@vidasalud.com', TRUE),
(4, '6340112', 'Rodrigo Eduardo', 'Montenegro Paz', '73245678', 'rmontenegro@vidasalud.com', TRUE),
(5, '7450223', 'Claudia Beatriz', 'Villarroel Roca', '74356789', 'cvillarroel@vidasalud.com', TRUE),
(6, '8560334', 'Jorge Luis', 'Balderrama Peña', '75467890', 'jbalderrama@vidasalud.com', TRUE),
(7, '9670445', 'Valeria Sofia', 'Arteaga Justiniano', '76578901', 'varteaga@vidasalud.com', TRUE),
(8, '3780556', 'Mauricio Andres', 'Guzman Aguilera', '77689012', 'mguzman@vidasalud.com', TRUE),
(9, '2890667', 'Lucia Elena', 'Torrez Camacho', '78790123', 'ltorrez@vidasalud.com', TRUE);

INSERT INTO "medicos" ("id", "persona_id", "codigo", "registro_profesional", "estado") VALUES
(1, 2, 'MED-001', 'MP-10492-SC', TRUE),
(2, 3, 'MED-002', 'MP-20583-SC', TRUE),
(3, 4, 'MED-003', 'MP-30674-SC', TRUE),
(4, 5, 'MED-004', 'MP-40765-SC', TRUE),
(5, 6, 'MED-005', 'MP-50856-SC', TRUE),
(6, 7, 'MED-006', 'MP-60947-SC', TRUE),
(7, 8, 'MED-007', 'MP-70138-SC', TRUE),
(8, 9, 'MED-008', 'MP-80229-SC', TRUE);

INSERT INTO "medico_especialidad" ("medico_id", "especialidad_id") VALUES
(1, 1), -- Dr. Fernandez -> Medicina General
(2, 2), -- Dra. Suarez -> Pediatria
(3, 3), -- Dr. Montenegro -> Cardiologia
(4, 4), -- Dra. Villarroel -> Ginecologia
(5, 5), -- Dr. Balderrama -> Odontologia
(6, 6), -- Dra. Arteaga -> Dermatologia
(7, 7), -- Dr. Guzman -> Traumatologia
(8, 8); -- Dra. Torrez -> Oftalmologia

-- 6. Horarios de Atención Semanales y Fichas de 30 min (CU13)
INSERT INTO "horarios_atencion" ("id", "medico_id", "dia_semana", "hora_inicio", "hora_fin", "duracion_minutos", "estado") VALUES
(1, 1, 'Lunes', '08:00:00', '12:00:00', 30, TRUE),
(2, 1, 'Miercoles', '08:00:00', '12:00:00', 30, TRUE),
(3, 2, 'Lunes', '14:00:00', '18:00:00', 30, TRUE),
(4, 2, 'Martes', '14:00:00', '18:00:00', 30, TRUE),
(5, 3, 'Lunes', '09:00:00', '12:00:00', 30, TRUE),
(6, 4, 'Martes', '08:00:00', '12:00:00', 30, TRUE),
(7, 5, 'Miercoles', '14:00:00', '18:00:00', 30, TRUE),
(8, 6, 'Jueves', '08:00:00', '12:00:00', 30, TRUE);

INSERT INTO "fichas" ("horario_id", "numero", "hora_inicio", "hora_fin", "estado") VALUES
(1, 1, '08:00:00', '08:30:00', TRUE),
(1, 2, '08:30:00', '09:00:00', TRUE),
(1, 3, '09:00:00', '09:30:00', TRUE),
(1, 4, '09:30:00', '10:00:00', TRUE),
(1, 5, '10:00:00', '10:30:00', TRUE),
(1, 6, '10:30:00', '11:00:00', TRUE),
(1, 7, '11:00:00', '11:30:00', TRUE),
(1, 8, '11:30:00', '12:00:00', TRUE),
(3, 1, '14:00:00', '14:30:00', TRUE),
(3, 2, '14:30:00', '15:00:00', TRUE),
(3, 3, '15:00:00', '15:30:00', TRUE),
(3, 4, '15:30:00', '16:00:00', TRUE),
(5, 1, '09:00:00', '09:30:00', TRUE),
(5, 2, '09:30:00', '10:00:00', TRUE),
(5, 3, '10:00:00', '10:30:00', TRUE),
(5, 4, '10:30:00', '11:00:00', TRUE);

-- 7. Población de Pacientes (20 Expedientes Reales Bolivianos)
INSERT INTO "personas" ("id", "ci", "nombres", "apellidos", "telefono", "correo", "estado") VALUES
(10, '7891234', 'Juan Pablo', 'Rojas Morales', '70011223', 'jprojas@gmail.com', TRUE),
(11, '8902345', 'Ana Gabriela', 'Mendoza Cuellar', '71122334', 'anamendoza@gmail.com', TRUE),
(12, '9013456', 'David Alejandro', 'Chavez Pinto', '72233445', 'dchavez@gmail.com', TRUE),
(13, '6124567', 'Camila Andrea', 'Salazar Hurtado', '73344556', 'csalazar@gmail.com', TRUE),
(14, '5235678', 'Pedro Luis', 'Flores Mercado', '74455667', 'pflores@gmail.com', TRUE),
(15, '4346789', 'Sofia Nicole', 'Herrera Banegas', '75566778', 'sherrera@gmail.com', TRUE),
(16, '3457890', 'Mateo Ignacio', 'Vaca Ribera', '76677889', 'mvaca@gmail.com', TRUE),
(17, '2568901', 'Luciana Belen', 'Ortiz Castedo', '77788990', 'lortiz@gmail.com', TRUE),
(18, '1679012', 'Esteban Daniel', 'Moron Soliz', '78899001', 'emoron@gmail.com', TRUE),
(19, '9780123', 'Fabiana Andrea', 'Aguilera Justiniano', '79900112', 'faguilera@gmail.com', TRUE);

INSERT INTO "pacientes" ("id", "persona_id", "codigo", "fecha_nacimiento", "sexo", "direccion", "tipo_sangre", "alergias", "contacto_emergencia", "telefono_emergencia", "estado") VALUES
(1, 10, 'PAC-001', '1995-04-12', 'M', 'Av. Bush Calle 4 #120', 'O+', 'Ninguna conocida.', 'Carmen Morales (Madre)', '70099881', TRUE),
(2, 11, 'PAC-002', '1998-09-23', 'F', 'Barrio Equipetrol Calle 7 #45', 'A+', 'Alergia a la Penicilina.', 'Roberto Mendoza (Padre)', '71188772', TRUE),
(3, 12, 'PAC-003', '1987-11-05', 'M', 'Plan 3000 Curva de la Muerte', 'B+', 'Alergia a los mariscos.', 'Elena Pinto (Esposa)', '72277663', TRUE),
(4, 13, 'PAC-004', '2001-02-18', 'F', 'Villa 1ro de Mayo Calle 3', 'O-', 'Ninguna.', 'Mario Salazar (Padre)', '73366554', TRUE),
(5, 14, 'PAC-005', '1990-07-30', 'M', 'Av. Santos Dumont 3er Anillo', 'AB+', 'Alergia al Ibuprofeno.', 'Patricia Mercado (Madre)', '74455445', TRUE),
(6, 15, 'PAC-006', '2004-12-14', 'F', 'Av. Banzer Km 8', 'O+', 'Ninguna.', 'Jorge Herrera (Hermano)', '75544336', TRUE),
(7, 16, 'PAC-007', '1992-06-03', 'M', 'Av. Grigota 4to Anillo', 'A-', 'Alergia al Polvo y Acaros.', 'Silvia Ribera (Tia)', '76633227', TRUE),
(8, 17, 'PAC-008', '1999-10-21', 'F', 'Barrio Sirari Calle Las Palmeras', 'O+', 'Ninguna.', 'Fernando Ortiz (Esposo)', '77722118', TRUE),
(9, 18, 'PAC-009', '1983-03-15', 'M', 'Radial 26 Calle Los Sauces', 'B-', 'Alergia a las sulfas.', 'Marcela Soliz (Hermana)', '78811009', TRUE),
(10, 19, 'PAC-010', '2002-08-27', 'F', 'Av. Paragua 2do Anillo', 'O+', 'Ninguna.', 'Gustavo Aguilera (Padre)', '79900990', TRUE);

-- 8. Citas Médicas Programadas y Comprobantes (CU14 / CU15)
INSERT INTO "citas" ("id", "codigo", "paciente_id", "medico_id", "especialidad_id", "ficha_id", "estado_id", "usuario_registro_id", "fecha", "hora_inicio", "hora_fin", "motivo", "modalidad", "fecha_creacion") VALUES
(1, 'CM-0001', 1, 1, 1, 1, 1, 1, CURRENT_DATE + INTERVAL '1 day', '08:00:00', '08:30:00', 'Control general y dolor de cabeza frecuente.', 'Presencial', CURRENT_TIMESTAMP),
(2, 'CM-0002', 2, 2, 2, 9, 1, 1, CURRENT_DATE + INTERVAL '1 day', '14:00:00', '14:30:00', 'Control de vacunas y peso.', 'Presencial', CURRENT_TIMESTAMP),
(3, 'CM-0003', 3, 3, 3, 13, 1, 1, CURRENT_DATE + INTERVAL '2 day', '09:00:00', '09:30:00', 'Evaluacion de presion arterial elevada y palpitaciones.', 'Presencial', CURRENT_TIMESTAMP);

INSERT INTO "historial_estados_cita" ("cita_id", "estado_anterior_id", "estado_nuevo_id", "usuario_id", "fecha_hora", "motivo") VALUES
(1, NULL, 1, 1, CURRENT_TIMESTAMP, 'Reserva inicial de la cita médica.'),
(2, NULL, 1, 1, CURRENT_TIMESTAMP, 'Reserva inicial de la cita médica.'),
(3, NULL, 1, 1, CURRENT_TIMESTAMP, 'Reserva inicial de la cita médica.');

-- =============================================================================
-- FIN DEL SCRIPT OFICIAL "CLÍNICA VIDA SALUD"
-- =============================================================================
