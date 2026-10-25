@extends('layouts.app')

@section('content')
<a class="back-link" href="{{ route('eventos.dashboard') }}">
    <span aria-hidden="true">←</span> Volver a eventos
</a>

<section class="panel-card create-page-card">
    <header class="create-page-header">
        <span class="hero-kicker"><span aria-hidden="true">▣</span> Registro de Evento</span>
        <h1>Iniciar nuevo evento</h1>
        <p class="muted">Completa los requerimientos solicitados por la base de datos corporativa para iniciar la planificación.</p>
    </header>

    <form class="event-create-form" action="{{ route('eventos.store') }}" method="POST">
        @csrf
        <input type="hidden" name="folio" id="input-page-folio" value="EVT-{{ str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT) }}">
        <input type="hidden" name="estado" value="Planificando">

        <div class="folio-preview-box">
            <span class="muted">Folio asignado:</span>
            <strong class="folio-code" id="display-page-folio">EVT-{{ str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT) }}</strong>
        </div>

        <div class="form-grid-modal">
            <label>
                <span>Nombre del evento <b class="required">*</b></span>
                <input name="nombre" type="text" placeholder="ej. Boda de Laura y Carlos" maxlength="120" required autofocus>
            </label>

            <label>
                <span>Tipo de evento <b class="required">*</b></span>
                <select name="tipo" required>
                    <option value="Boda">Boda</option>
                    <option value="XV Años">XV Años</option>
                    <option value="Cumpleaños">Cumpleaños</option>
                    <option value="Graduación">Graduación</option>
                    <option value="Bautizo">Bautizo</option>
                    <option value="Corporativo">Corporativo</option>
                    <option value="Reunión Social">Reunión Social</option>
                </select>
            </label>

            <label>
                <span>Fecha del evento <b class="required">*</b></span>
                <input name="fecha_evento" type="date" required value="{{ date('Y-m-d', strtotime('+7 days')) }}">
            </label>

            <label>
                <span>Ubicación o Recinto <b class="required">*</b></span>
                <input name="ubicacion" type="text" placeholder="ej. Salón Los Ángeles, Toluca" maxlength="160" required>
            </label>

            <label>
                <span>Número estimado de invitados</span>
                <input name="numero_invitados" type="number" min="0" max="10000" placeholder="30" value="30">
            </label>

            <label>
                <span>Presupuesto planeado ($ MXN)</span>
                <input name="presupuesto" type="number" min="0" step="500" placeholder="100000" value="100000">
            </label>
        </div>

        <label class="form-row-full">
            <span>Reglamento / Observaciones generales</span>
            <input name="reglamento" type="text" placeholder="ej. No niños, código de vestimenta formal, acceso con QR" maxlength="500">
        </label>

        @if($errors->any())
            <p class="form-error">{{ $errors->first() }}</p>
        @endif

        <footer class="modal-footer">
            <a href="{{ route('eventos.dashboard') }}" class="button button--secondary">Cancelar</a>
            <button type="submit" class="button button--primary">
                <span aria-hidden="true">＋</span> Guardar y comenzar evento
            </button>
        </footer>
    </form>
</section>
@endsection
