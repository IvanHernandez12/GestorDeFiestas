@extends('layouts.app')

@section('content')
<section class="hero">
    <span class="hero-kicker"><span aria-hidden="true">▣</span> Gestión de eventos</span>
    <h1>Organiza tu evento<br><span>de principio a fin.</span></h1>
    <p>Crea el evento, recibe recomendaciones de proveedores, gestiona invitados y comparte invitaciones con QR.</p>
    <a class="button button--primary" href="#tus-eventos">Iniciar evento <span aria-hidden="true">→</span></a>
</section>

<section class="events-section" id="tus-eventos" data-event-list data-api-url="{{ url('/api/eventos') }}">
    <div class="section-heading">
        <h2>Tus eventos</h2>
        <span class="muted" data-event-count></span>
    </div>
    <div class="event-grid" data-events-container>
        <p class="loading-state">Cargando eventos…</p>
    </div>
    <template id="event-card-template"><x-event-card /></template>
</section>
@endsection
