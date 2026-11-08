<?php

/*
 * Mensajes de validación en español (APP_LOCALE=es).
 * Los controladores definen mensajes propios para las reglas de negocio; estos cubren el resto.
 */
return [
    'accepted' => 'Debe aceptar :attribute.',
    'after' => ':Attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => ':Attribute debe ser una fecha posterior o igual a :date.',
    'alpha_dash' => ':Attribute solo puede contener letras, números, guiones y guiones bajos.',
    'array' => ':Attribute debe ser una lista.',
    'before' => ':Attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => ':Attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'numeric' => ':Attribute debe estar entre :min y :max.',
        'string' => ':Attribute debe tener entre :min y :max caracteres.',
        'array' => ':Attribute debe tener entre :min y :max elementos.',
    ],
    'boolean' => ':Attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => ':Attribute no es una fecha válida.',
    'date_format' => ':Attribute no tiene el formato :format.',
    'different' => ':Attribute y :other deben ser diferentes.',
    'distinct' => ':Attribute tiene un valor repetido.',
    'email' => ':Attribute debe ser un correo electrónico válido.',
    'exists' => ':Attribute seleccionado no es válido.',
    'in' => ':Attribute seleccionado no es válido.',
    'integer' => ':Attribute debe ser un número entero.',
    'max' => [
        'numeric' => ':Attribute no debe ser mayor que :max.',
        'string' => ':Attribute no debe tener más de :max caracteres.',
        'array' => ':Attribute no debe tener más de :max elementos.',
        'file' => ':Attribute no debe pesar más de :max kilobytes.',
    ],
    'min' => [
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
        'array' => ':Attribute debe tener al menos :min elementos.',
        'file' => ':Attribute debe pesar al menos :min kilobytes.',
    ],
    'numeric' => ':Attribute debe ser un número.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'same' => ':Attribute y :other deben coincidir.',
    'string' => ':Attribute debe ser un texto.',
    'unique' => ':Attribute ya está registrado.',

    'attributes' => [
        'ci' => 'CI', 'nombres' => 'nombres', 'apellidos' => 'apellidos', 'telefono' => 'teléfono', 'correo' => 'correo',
        'email' => 'correo', 'username' => 'nombre de usuario', 'password' => 'contraseña', 'password_actual' => 'contraseña actual',
        'fecha_nacimiento' => 'fecha de nacimiento', 'sexo' => 'sexo', 'direccion' => 'dirección', 'tipo_sangre' => 'tipo de sangre',
        'paciente_id' => 'paciente', 'medico_id' => 'médico', 'especialidad_id' => 'especialidad', 'ficha_id' => 'ficha',
        'fecha' => 'fecha', 'modalidad' => 'modalidad', 'motivo' => 'motivo', 'motivo_cancelacion' => 'motivo de cancelación',
        'dia_semana' => 'día', 'hora_inicio' => 'hora de inicio', 'hora_fin' => 'hora de fin', 'duracion_cita_min' => 'duración',
        'fecha_inicio' => 'fecha inicial', 'fecha_fin' => 'fecha final', 'desde' => 'fecha desde', 'hasta' => 'fecha hasta',
        'diagnostico' => 'diagnóstico', 'presion_arterial' => 'presión arterial', 'temperatura' => 'temperatura', 'peso' => 'peso',
        'nombre' => 'nombre', 'nombre_comercial' => 'nombre comercial', 'principio_activo' => 'principio activo', 'presentacion' => 'presentación',
        'alergia_id' => 'alergia', 'severidad' => 'severidad', 'tipo' => 'tipo', 'descripcion' => 'descripción', 'rol_id' => 'rol',
        'contacto_emerg_nombre' => 'nombre del contacto de emergencia', 'contacto_emerg_telefono' => 'teléfono de emergencia',
    ],
];
