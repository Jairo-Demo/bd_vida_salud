@extends('layouts.app')
@section('title', 'Alergias y Antecedentes')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
@include('partials.selector-pacientes', [
    'ruta' => 'antecedentes.index', 'titulo' => '🧾 Alergias y Antecedentes (P7)',
    'descripcion' => 'Seleccione un paciente de su agenda para registrar alergias, antecedentes y medicación habitual (CU24).',
    'accion' => 'Abrir',
])
@endsection
