<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Clínica Vida Salud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f766e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-header {
            background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
            color: #ffffff;
            padding: 32px 28px 28px;
            text-align: center;
        }
        .clinic-icon {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            backdrop-filter: blur(8px);
        }
        .clinic-icon svg {
            width: 34px;
            height: 34px;
            fill: #ffffff;
        }
        .card-header h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .card-header p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
            margin-top: 4px;
        }
        .card-body {
            padding: 32px 28px 24px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .input-group {
            position: relative;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            color: #0f172a;
            transition: all 0.2s;
            outline: none;
        }
        .form-control:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            font-size: 13px;
            color: #64748b;
        }
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .checkbox-container input {
            cursor: pointer;
            width: 16px;
            height: 16px;
            accent-color: #0284c7;
        }
        .btn-primary {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }
        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.4);
        }
        .card-footer {
            background-color: #f8fafc;
            border-top: 1px solid #f1f5f9;
            padding: 18px 24px;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        .demo-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
            margin-top: 10px;
        }
        .badge {
            background-color: #e2e8f0;
            color: #334155;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-family: monospace;
            cursor: pointer;
            transition: background 0.2s;
        }
        .badge:hover {
            background-color: #cbd5e1;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="card-header">
        <div class="clinic-icon">
            <!-- Icono Cruz Medica -->
            <svg viewBox="0 0 24 24">
                <path d="M19 10.5h-5.5V5c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v5.5H5c-.83 0-1.5.67-1.5 1.5s.67 1.5 1.5 1.5h5.5V19c0 .83.67 1.5 1.5 1.5s1.5-.67 1.5-1.5v-5.5H19c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5z"/>
            </svg>
        </div>
        <h1>Clínica Vida Salud</h1>
        <p>Sistema de Gestión de Citas Médicas</p>
    </div>

    <div class="card-body">
        {{-- Mensajes de estado --}}
        @if(session('success'))
            <div class="alert alert-success">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->has('login'))
            <div class="alert alert-error">
                <span>{{ $errors->first('login') }}</span>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <!-- Campo Usuario o Email -->
            <div class="form-group">
                <label class="form-label" for="login">Usuario o Correo Electrónico</label>
                <input 
                    type="text" 
                    id="login" 
                    name="login" 
                    class="form-control" 
                    value="{{ old('login') }}" 
                    placeholder="Ej. admin o correo@ejemplo.com"
                    required 
                    autofocus
                >
            </div>

            <!-- Campo Contraseña -->
            <div class="form-group">
                <label class="form-label" for="password">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="••••••••" 
                    required
                >
            </div>

            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Recordar sesión</span>
                </label>
            </div>

            <button type="submit" class="btn-primary">
                Iniciar Sesión
            </button>
        </form>
    </div>

    <div class="card-footer">
        <div><strong>Cuentas de Acceso Rápido:</strong></div>
        <div class="demo-badges">
            <span class="badge" onclick="fillCreds('admin', 'admin123')">Admin: admin</span>
            <span class="badge" onclick="fillCreds('recepcion', 'recep123')">Recep: recepcion</span>
            <span class="badge" onclick="fillCreds('dr.roca', 'medico123')">Médico: dr.roca</span>
            <span class="badge" onclick="fillCreds('paciente1', 'paciente123')">Paciente: paciente1</span>
        </div>
    </div>
</div>

<script>
    // Relleno automatico al hacer clic en las credenciales demo
    function fillCreds(u, p) {
        document.getElementById('login').value = u;
        document.getElementById('password').value = p;
    }
</script>

</body>
</html>
