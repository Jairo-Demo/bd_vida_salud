{{-- Lista de pacientes de la agenda del médico. Variables: $pacientes, $buscar, $ruta, $titulo, $descripcion, $accion --}}
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $titulo }}</h1>
        <p class="page-subtitle">{{ $descripcion }}</p>
    </div>
</div>
<div class="card" style="padding:0; overflow-x:auto">
    <form method="GET" action="{{ route($ruta) }}" style="display:flex; gap:8px; padding:14px 16px">
        <input name="buscar" class="form-control" placeholder="Buscar por CI, nombre o apellido..." value="{{ $buscar }}">
        <button class="btn btn-light">Buscar</button>
    </form>
    <table class="data-table">
        <thead><tr><th>Código</th><th>CI</th><th>Paciente</th><th>Edad</th><th style="text-align:right">Acción</th></tr></thead>
        <tbody>
        @forelse($pacientes as $p)
            <tr>
                <td>{{ $p->codigo }}</td>
                <td>{{ $p->persona->ci ?? '—' }}</td>
                <td><strong>{{ $p->nombre_completo }}</strong></td>
                <td>{{ $p->fecha_nacimiento ? \Carbon\Carbon::parse($p->fecha_nacimiento)->age . ' años' : '—' }}</td>
                <td style="text-align:right"><a href="{{ route($ruta, ['paciente' => $p->id]) }}" class="act act-info">{{ $accion }}</a></td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center; color:#64748b; padding:22px">No hay pacientes en su agenda con ese criterio.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="padding: 10px 16px">{{ $pacientes->links() }}</div>
</div>
