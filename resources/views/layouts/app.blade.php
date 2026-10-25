<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clínica Vida Salud') - Sistema de Gestión de Citas Médicas</title>
    
    <!-- Fuentes Google: Plus Jakarta Sans -->
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
            display: flex;
            flex-direction: column;
        }
        /* Barra de navegacion superior */
        .navbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #0f172a;
            font-weight: 700;
            font-size: 17px;
        }
        .brand-icon {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
            color: #ffffff;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }
        /* Menu de Paquetes PUDS */
        .package-menu {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dropdown {
            position: relative;
        }
        .dropdown-btn {
            background: transparent;
            border: 1px solid transparent;
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .dropdown-btn:hover {
            background-color: #f1f5f9;
            color: #0284c7;
        }
        .pkg-badge {
            background-color: #e0f2fe;
            color: #0284c7;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 5px;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 4px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            min-width: 260px;
            padding: 8px 0;
            z-index: 200;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-header {
            padding: 8px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 1px solid #f1f5f9;
        }
        .dropdown-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 16px;
            font-size: 13px;
            color: #334155;
            text-decoration: none;
            transition: background 0.15s;
        }
        .dropdown-item:hover {
            background-color: #f8fafc;
            color: #0284c7;
        }
        .dropdown-item.disabled {
            color: #94a3b8;
            cursor: not-allowed;
            background: transparent;
        }
        .badge-cycle {
            font-size: 10px;
            background-color: #f1f5f9;
            color: #64748b;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }
        /* Area de usuario a la derecha */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .user-pill {
            text-align: right;
            line-height: 1.25;
        }
        .user-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .user-role-badge {
            font-size: 11px;
            font-weight: 600;
            color: #0284c7;
        }
        .btn-logout {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 7px 13px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-logout:hover {
            background-color: #fca5a5;
            color: #7f1d1d;
        }
        /* Contenedor principal */
        .main-wrapper {
            max-width: 1280px;
            margin: 28px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }
        /* Alertas flash */
        .alert {
            padding: 12px 18px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .alert-success {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        /* Footer institucional */
        .footer {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 20px 0;
            margin-top: auto;
            font-size: 12px;
            color: #64748b;
            text-align: center;
        }
        @media (max-width: 900px) {
            .package-menu { display: none; }
            .user-pill { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>

@php
    $usuarioActual = Auth::user();
    $paquetesMenu = \App\Services\MenuService::paquetes($usuarioActual);
@endphp

<!-- Barra de Navegacion Superior -->
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-left">
            <a href="{{ route('dashboard') }}" class="brand">
                <div class="brand-icon">+</div>
                <span>Clínica Vida Salud</span>
            </a>

            <!-- Desplegables de los Paquetes PUDS -->
            <div class="package-menu">
                @foreach($paquetesMenu as $pkg)
                    <div class="dropdown">
                        <button class="dropdown-btn">
                            <span class="pkg-badge">{{ $pkg['codigo'] }}</span>
                            <span>{{ $pkg['corto'] }}</span>
                            <span style="font-size: 9px; margin-left: 2px;">▼</span>
                        </button>
                        <div class="dropdown-content">
                            <div class="dropdown-header">
                                {{ $pkg['codigo'] }} · {{ $pkg['nombre'] }}
                            </div>
                            @foreach($pkg['opciones'] as $opc)
                                @if(!empty($opc['ruta']) && Route::has($opc['ruta']))
                                    <a href="{{ route($opc['ruta']) }}" class="dropdown-item">
                                        <span>{{ $opc['nombre'] }}</span>
                                        <span style="color: #94a3b8;">&rarr;</span>
                                    </a>
                                @else
                                    <div class="dropdown-item disabled">
                                        <span>{{ $opc['nombre'] }}</span>
                                        <span class="badge-cycle">Próximo ciclo</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Perfil del usuario activo y boton de salida -->
        <div class="nav-right">
            <div class="user-pill">
                <div class="user-name">{{ $usuarioActual->name ?? 'Usuario' }}</div>
                <div class="user-role-badge">{{ $usuarioActual->rol->nombre ?? 'Sin Rol' }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn-logout">Cerrar Sesión</button>
            </form>
        </div>
    </div>
</nav>

<!-- Area de Contenido Central -->
<main class="main-wrapper">
    {{-- Mensajes Flash de exito o error --}}
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

    @yield('content')
</main>

<!-- Pie de Pagina Academico PUDS -->
<footer class="footer">
    <div style="max-width: 1280px; margin: 0 auto; padding: 0 20px;">
        <strong>Clínica "Vida Salud"</strong> — Sistema Web de Gestión de Citas Médicas | Sistemas de Información I (UAGRM) | Ciclo 1
    </div>
</footer>

@stack('scripts')
</body>
</html>
