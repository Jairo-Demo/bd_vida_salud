@extends('layouts.app')
@section('title', 'Historia Clínica')
@push('styles') @include('partials.clinico-styles') @endpush
@section('content')
@include('partials.selector-pacientes', [
    'ruta' => 'historia.index', 'titulo' => '📚 Historia Clínica (P7)',
    'descripcion' => 'Seleccione un paciente de su agenda para ver su historia clínica (CU28).',
    'accion' => 'Ver historia',
])
@endsection
