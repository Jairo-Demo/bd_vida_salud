@extends('layouts.app')

@section('title', 'Panel Principal')

@push('styles')
<style>
    /* Tarjeta de bienvenida superior */
    .welcome-banner {
        background: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);
        border-radius: 16px;
        padding: 28px 32px;
        color: #ffffff;
        margin-bottom: 28px;
        box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.25);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .welcome-title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 6px;
    }
    .welcome-desc {
        font-size: 13.5px;
        color: rgba(255, 255, 255, 0.9);
    }
    .role-badge-large {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    /* Grilla de metricas rapidas */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 18px;
        margin-bottom: 32px;
    }
    .metric-card {
        background: #ffffff;
        padding: 20px;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .metric-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }
    .metric-val {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 6px;
    }
    .metric-sub {
        font-size: 12px;
        color: #0284c7;
        margin-top: 4px;
        font-weight: 500;
    }

    /* Seccion de Paquetes PUDS */
    .packages-section-title {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .packages-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 20px;
        margin-bottom: 32px;
    }
    .package-card {
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    }
    .package-card:hover {
        transform: translateY(-2px);
        border-color: #bae6fd;
        box-shadow: 0 8px 20px -4px rgba(2, 132, 199, 0.12);
    }
    .package-card-header {
        padding: 20px 22px 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .package-card-code {
        display: inline-block;
        background-color: #e0f2fe;
        color: #0284c7;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 6px;
        margin-bottom: 8px;
    }
    .package-card-name {
        font-size: 16px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .package-card-desc {
        font-size: 12.5px;
        color: #64748b;
        line-height: 1.45;
    }
    .package-card-body {
        padding: 10px;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .option-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 14px;
        border-radius: 8px;
        text-decoration: none;
        color: #334155;
        font-size: 13.5px;
        font-weight: 600;
        transition: all 0.15s;
    }
    .option-link:hover {
        background-color: #f0fdf4;
        color: #166534;
    }
    .option-link.disabled {
        color: #94a3b8;
        cursor: not-allowed;
        background: transparent;
    }
    .option-link.disabled:hover {
        background: transparent;
        color: #94a3b8;
    }
    .tag-cycle {
        font-size: 10.5px;
        background-color: #f1f5f9;
        color: #64748b;
        padding: 3px 7px;
        border-radius: 5px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')

@php
    $paquetesGrid = \App\Services\MenuService::paquetes($user);
@endphp

<!-- Banner de Bienvenida -->
<div class="welcome-banner">
    <div>
        <h1 class="welcome-title">¡Bienvenido, {{ $user->name }}!</h1>
        <p class="welcome-desc">Arquitectura por Paquetes PUDS — Clínica "Vida Salud" (Ciclo 1)</p>
    </div>
    <div class="role-badge-large">
        Perfil: {{ $rol }}
    </div>
</div>

<!-- Metricas del Sistema -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-label">Pacientes Registrados</div>
        <div class="metric-val">{{ $totalPacientes }}</div>
        <div class="metric-sub">P2: Gestión de Pacientes</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Médicos Activos</div>
        <div class="metric-val">{{ $totalMedicos }}</div>
        <div class="metric-sub">P3: Gestión Médica y Fichas</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Citas Médicas Hoy</div>
        <div class="metric-val">{{ $citasHoy }}</div>
        <div class="metric-sub">P4: Programación de Citas</div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Último Acceso</div>
        <div class="metric-val" style="font-size: 16px; margin-top: 14px; color: #475569;">
            {{ $user->ultimo_acceso ? $user->ultimo_acceso->format('d/m/Y H:i') : 'Primer acceso' }}
        </div>
        <div class="metric-sub">P1: Seguridad y Bitácora</div>
    </div>
</div>

<!-- Cuadricula de Paquetes PUDS -->
<div class="packages-section-title">
    <span>📦 Paquetes del Sistema (Metodología PUDS - Ciclo 1)</span>
</div>

<div class="packages-grid">
    @foreach($paquetesGrid as $pkg)
        <div class="package-card">
            <div class="package-card-header">
                <span class="package-card-code">{{ $pkg['codigo'] }}</span>
                <h2 class="package-card-name">{{ $pkg['nombre'] }}</h2>
                <p class="package-card-desc">{{ $pkg['descripcion'] }}</p>
            </div>
            <div class="package-card-body">
                @foreach($pkg['opciones'] as $opcion)
                    @if(!empty($opcion['ruta']) && Route::has($opcion['ruta']))
                        <a href="{{ route($opcion['ruta']) }}" class="option-link">
                            <span>{{ $opcion['nombre'] }}</span>
                            <span style="color: #0284c7;">&rarr;</span>
                        </a>
                    @else
                        {{-- Opcion del Ciclo 2 o modulo en construccion: badge 'Proximo ciclo' --}}
                        <div class="option-link disabled" title="{{ $opcion['descripcion'] }}">
                            <span>{{ $opcion['nombre'] }}</span>
                            <span class="tag-cycle">
                                {{ ($opcion['ciclo'] ?? 2) == 2 ? 'Próximo ciclo' : 'En desarrollo' }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@endsection
