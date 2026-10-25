@extends('layouts.app')

@section('title', 'Especialidad: ' . $especialidad->nombre)

@push('styles')
<style>
    .show-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px 28px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .header-area { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px; }
    .title { font-size: 20px; font-weight: 700; color: #0f172a; }
    .btn-back { background: #f1f5f9; color: #475569; padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; }
    .table-container { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow-x: auto; margin-top: 16px; }
    .table { width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left; }
    .table th { background: #f8fafc; color: #475569; padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-size: 11.5px; text-transform: uppercase; }
    .table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
</style>
@endpush

@section('content')

<div class="show-card">
    <div class="header-area">
        <div>
            <h1 class="title">{{ $especialidad->nombre }}</h1>
            <p style="font-size: 13px; color: #64748b; margin-top: 2px;">
                Identificador: <code>{{ $especialidad->nombre_normalizado }}</code> | 
                Estado: <strong>{{ $especialidad->estado ? 'Activa' : 'Inactiva' }}</strong>
            </p>
        </div>
        <a href="{{ route('especialidades.index') }}" class="btn-back">&larr; Volver a Especialidades</a>
    </div>

    <p style="font-size: 14px; color: #334155; margin-top: 8px;">
        {{ $especialidad->descripcion ?: 'Sin descripción detallada.' }}
    </p>
</div>

<div class="show-card">
    <h2 style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Médicos Asignados a esta Especialidad</h2>
    <p style="font-size: 13px; color: #64748b; margin-bottom: 16px;">Facultativos autorizados para brindar consulta en esta rama clínica.</p>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Médico</th>
                    <th>Registro Profesional</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                @forelse($especialidad->medicos as $med)
                    <tr>
                        <td><strong>{{ $med->codigo }}</strong></td>
                        <td><strong>{{ $med->persona->nombre_completo ?? '—' }}</strong></td>
                        <td>{{ $med->registro_profesional }}</td>
                        <td>{{ $med->persona->telefono ?? '—' }}</td>
                        <td>{{ $med->persona->correo ?? '—' }}</td>
                        <td>
                            <a href="{{ route('medicos.show', $med) }}" style="color: #0284c7; font-weight: 600; text-decoration: none;">Ver Perfil &rarr;</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 24px; color: #64748b;">
                            Aún no hay médicos asignados a esta especialidad.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
