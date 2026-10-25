<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal - Clínica Vida Salud</title>
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
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
        }
        .brand-badge {
            background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
            color: #ffffff;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .user-nav {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .user-info {
            text-align: right;
        }
        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }
        .user-role {
            font-size: 12px;
            color: #0284c7;
            font-weight: 600;
        }
        .btn-logout {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background-color: #fecaca;
        }
        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 20px;
        }
        .welcome-card {
            background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
            color: #ffffff;
            padding: 32px;
            border-radius: 18px;
            margin-bottom: 28px;
            box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.25);
        }
        .welcome-card h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .welcome-card p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: #ffffff;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-top: 8px;
        }
        .info-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
        }
        .info-card h3 {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .info-card ul {
            list-style: none;
            display: grid;
            gap: 10px;
            font-size: 14px;
            color: #475569;
        }
        .info-card li {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
        }
    </style>
</head>
<body>

<!-- Barra de Navegación Superior -->
<nav class="navbar">
    <div class="brand">
        <div class="brand-badge">+</div>
        <span>Clínica Vida Salud</span>
    </div>
    <div class="user-nav">
        <div class="user-info">
            <div class="user-name">{{ $user->name }}</div>
            <div class="user-role">{{ $rol }}</div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn-logout">Cerrar Sesión</button>
        </form>
    </div>
</nav>

<!-- Contenedor Principal -->
<div class="container">
    <!-- Tarjeta de Bienvenida -->
    <div class="welcome-card">
        <h2>¡Bienvenido, {{ $user->name }}!</h2>
        <p>Sistema de Gestión de Citas Médicas — Panel de Control (Perfil: {{ $rol }})</p>
    </div>

    <!-- Grilla de Estadísticas Rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Pacientes Registrados</div>
            <div class="stat-value">{{ $totalPacientes }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Médicos Activos</div>
            <div class="stat-value">{{ $totalMedicos }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Citas Médicas Hoy</div>
            <div class="stat-value">{{ $citasHoy }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Último Acceso</div>
            <div class="stat-value" style="font-size: 16px; margin-top: 18px; color: #64748b;">
                {{ $user->ultimo_acceso ? $user->ultimo_acceso->format('d/m/Y H:i') : 'Primer acceso' }}
            </div>
        </div>
    </div>

    <!-- Tarjeta Informativa de Auditoría -->
    <div class="info-card">
        <h3>Auditoría y Seguridad Activa</h3>
        <ul>
            <li><span class="dot"></span> Sesión autenticada con protección CSRF e invalidación de token.</li>
            <li><span class="dot"></span> Registro automático en la tabla <code>bitacoras</code> (IP, canal WEB, timestamp).</li>
            <li><span class="dot"></span> Middleware de control de acceso por roles habilitado.</li>
        </ul>
    </div>
</div>

</body>
</html>
