<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $reporte['titulo'] }}</title>
    <style>
        * { font-family: DejaVu Sans, Arial, sans-serif; }
        body { font-size: 11px; color: #1e293b; margin: 24px; }
        .head { border-bottom: 2px solid #0284c7; padding-bottom: 8px; margin-bottom: 12px; }
        .brand { font-size: 18px; font-weight: bold; color: #0284c7; }
        h1 { font-size: 15px; margin: 6px 0 2px; }
        .muted { color: #64748b; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #e0f2fe; color: #0f172a; text-align: left; padding: 6px; font-size: 10px; border: 1px solid #cbd5e1; }
        td { padding: 5px 6px; border: 1px solid #e2e8f0; }
        tr.tot td { font-weight: bold; background: #f1f5f9; }
        .bars td { border: none; padding: 2px 4px; }
        .bar { background: #0d9488; height: 10px; }
        .print-btn { margin-bottom: 14px; padding: 8px 14px; background: #0284c7; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        @media print { .print-btn { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    @if($imprimir)
        <button class="print-btn" onclick="window.print()">🖨️ Imprimir / Guardar como PDF</button>
    @endif
    <div class="head">
        <div class="brand">Clínica Vida Salud</div>
        <h1>{{ $reporte['titulo'] }}</h1>
        <div class="muted">{{ $reporte['subtitulo'] }}{{ $filtros }} · Generado el {{ $generado->format('d/m/Y H:i') }}</div>
    </div>

    @php $max = max(array_merge([1], array_values($reporte['grafico']))); @endphp
    @if(!empty($reporte['grafico']))
        <table class="bars">
            @foreach(array_slice($reporte['grafico'], 0, 12, true) as $lbl => $val)
                <tr>
                    <td style="width: 32%">{{ $lbl }}</td>
                    <td><div class="bar" style="width: {{ max(1, round($val * 100 / $max)) }}%"></div></td>
                    <td style="width: 8%; text-align: right"><strong>{{ $val }}</strong></td>
                </tr>
            @endforeach
        </table>
    @endif

    <table>
        <thead><tr>@foreach($reporte['columnas'] as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($reporte['filas'] as $f)
            <tr>@foreach($reporte['columnas'] as $c)<td>{{ $f[$c] ?? '' }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($reporte['columnas']) }}">No existen datos para los filtros elegidos.</td></tr>
        @endforelse
        <tr class="tot">@foreach($reporte['columnas'] as $c)<td>{{ $reporte['totales'][$c] ?? '' }}</td>@endforeach</tr>
        </tbody>
    </table>
    @if($imprimir)
        <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
    @endif
</body>
</html>
