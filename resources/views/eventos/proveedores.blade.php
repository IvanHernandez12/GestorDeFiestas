@extends('layouts.app')

@section('content')
<a class="back-link" href="{{ route('eventos.dashboard') }}"><span aria-hidden="true">←</span> Volver a eventos</a>

<section class="provider-selection" data-provider-selection data-event-id="{{ $evento->id }}" data-api-url="{{ url('/api/eventos') }}">
    <header class="panel-card provider-selection__header">
        <span class="badge badge--type" data-event-type>{{ $evento->tipo }}</span>
        <h1 data-event-name>{{ $evento->nombre }}</h1>
        <p>Selecciona propuestas compatibles con el tipo de evento y tu presupuesto.</p>
        <strong>Presupuesto: <span data-event-budget>{{ '$' . number_format((float) $evento->presupuesto, 0) }}</span></strong>
    </header>

    <section class="panel-card provider-selection__summary" aria-label="Resumen del evento">
        <div><span class="muted">Fecha</span><strong data-event-date>{{ $evento->fecha_evento->format('d M Y') }}</strong></div>
        <div><span class="muted">Ubicación</span><strong data-event-location>{{ $evento->ubicacion }}</strong></div>
        <div><span class="muted">Invitados</span><strong data-event-guests>{{ $evento->numero_invitados }}</strong></div>
    </section>

    <section class="panel-card provider-selection__body">
        <div class="provider-selection__title">
            <div><h2>Propuestas de proveedores</h2><p class="muted">Cada propuesta usa como máximo el 35 % del presupuesto y la selección conjunta hasta el 70 %.</p></div>
            <strong>Total seleccionado: <span data-provider-total>$0</span></strong>
        </div>
        <div class="provider-options" data-provider-options><p class="muted">Buscando propuestas…</p></div>
        <p class="form-error" data-provider-feedback role="alert"></p>
        <button class="button button--primary" type="button" data-provider-continue>Guardar selección y ver información del evento</button>
    </section>
</section>
@endsection
