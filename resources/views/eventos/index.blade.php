@extends('layouts.app')

@section('content')
<section class="hero">
    <div class="hero-content">
        <span class="hero-kicker"><span aria-hidden="true">▣</span> Gestión de eventos · Bigbitbyte</span>
        <h1>Organiza tu evento<br><span>de principio a fin.</span></h1>
        <p>Crea el evento, define requerimientos de salón y mobiliario, gestiona la lista de comensales y comparte invitaciones inteligentes con código QR.</p>
        <div class="hero-actions">
            <button type="button" class="button button--primary button--lg" data-open-event-modal onclick="abrirModalEvento()" id="btn-iniciar-evento">
                <span aria-hidden="true">＋</span> Iniciar evento
            </button>
            <a class="button button--secondary button--lg" href="#tus-eventos">
                <span aria-hidden="true">▣</span> Ver catálogo
            </a>
        </div>
    </div>
</section>

<!-- Indicadores rápidos del sistema (Tarjetas modulares 150x100px estándar) -->
<section class="metrics-grid" aria-label="Resumen operativo">
    <article class="metric-card">
        <span class="metric-card__icon" aria-hidden="true">▣</span>
        <span class="metric-card__label">Planificación</span>
        <strong class="metric-card__value" id="stat-active-events">{{ isset($eventos) ? $eventos->count() : '—' }} eventos</strong>
        <span class="metric-card__hint">En seguimiento activo</span>
    </article>
    <article class="metric-card">
        <span class="metric-card__icon" aria-hidden="true">♧</span>
        <span class="metric-card__label">Asistencia</span>
        <strong class="metric-card__value" id="stat-total-guests">{{ isset($eventos) ? $eventos->sum('numero_invitados') : '—' }} comensales</strong>
        <span class="metric-card__hint">Capacidad estimada</span>
    </article>
    <article class="metric-card">
        <span class="metric-card__icon" aria-hidden="true">▦</span>
        <span class="metric-card__label">Distribución</span>
        <strong class="metric-card__value">Croquis 2D</strong>
        <span class="metric-card__hint">Mesas y áreas dinámicas</span>
    </article>
    <article class="metric-card">
        <span class="metric-card__icon" aria-hidden="true">▧</span>
        <span class="metric-card__label">Trazabilidad</span>
        <strong class="metric-card__value">Acceso QR</strong>
        <span class="metric-card__hint">Confirmación de asistencia</span>
    </article>
</section>

<!-- Catálogo principal de eventos -->
<section class="events-section" id="tus-eventos" data-event-list data-api-url="{{ url('/api/eventos') }}">
    <div class="section-heading">
        <div class="section-heading__title">
            <h2>Tus eventos</h2>
            <span class="muted" data-event-count>{{ isset($eventos) ? $eventos->count() . ' eventos' : '' }}</span>
        </div>
        <a class="button button--primary button--sm" data-open-event-modal onclick="abrirModalEvento()" href="#">
            <span aria-hidden="true">＋</span> Iniciar evento
        </a>
    </div>

    <div class="event-grid" data-events-container>
        @if(isset($eventos) && $eventos->isNotEmpty())
            @foreach($eventos as $evento)
                <x-event-card :evento="$evento" />
            @endforeach
        @else
            <p class="loading-state">Cargando eventos…</p>
        @endif
    </div>

    <template id="event-card-template">
        <x-event-card />
    </template>
</section>

<!-- Modal emergente para dar de alta un evento -->
<div class="modal-backdrop" id="modal-evento-backdrop" data-event-modal hidden onclick="if(event.target === this) cerrarModalEvento()">
    <div class="modal-dialog panel-card" role="dialog" aria-modal="true" aria-labelledby="modal-evento-titulo">
        <header class="modal-header">
            <div>
                <span class="hero-kicker modal-kicker"><span aria-hidden="true">▣</span> Registro de evento</span>
                <h2 id="modal-evento-titulo">Iniciar nuevo evento</h2>
                <p class="muted">Ingresa los requerimientos iniciales solicitados por la base de datos corporativa.</p>
            </div>
            <button type="button" class="modal-close" data-close-event-modal onclick="cerrarModalEvento()" aria-label="Cerrar ventana emergente">✕</button>
        </header>

        <form class="event-create-form" data-event-create-form action="{{ route('eventos.store') }}" method="POST">
            @csrf
            <!-- Folio autogenerado y estado inicial seguro -->
            <input type="hidden" name="folio" id="input-modal-folio" value="">
            <input type="hidden" name="estado" value="Planificando">

            <div class="folio-preview-box">
                <span class="muted">Folio asignado:</span>
                <strong class="folio-code" id="display-modal-folio">EVT-000000</strong>
                <button type="button" class="button-ghost" id="btn-regenerar-folio" onclick="regenerarFolioModal(event)" title="Regenerar folio aleatorio">
                    <span aria-hidden="true">↻</span> Generar otro
                </button>
            </div>

            <div class="form-grid-modal">
                <label>
                    <span>Nombre del evento <b class="required">*</b></span>
                    <input name="nombre" id="input-nombre-evento" type="text" placeholder="ej. Boda de Laura y Carlos" maxlength="120" required autofocus>
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

            <p class="form-error" data-event-form-error hidden></p>

            <footer class="modal-footer">
                <button type="button" class="button button--secondary" data-close-event-modal onclick="cerrarModalEvento()">Cancelar</button>
                <button type="submit" class="button button--primary" id="btn-confirmar-creacion">
                    <span aria-hidden="true">＋</span> Guardar y comenzar evento
                </button>
            </footer>
        </form>
    </div>
</div>

<script>
function abrirModalEvento() {
    const modal = document.getElementById('modal-evento-backdrop');
    if (modal) {
        regenerarFolioModal();
        modal.removeAttribute('hidden');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        setTimeout(function() {
            const input = document.getElementById('input-nombre-evento');
            if (input) input.focus();
        }, 50);
    }
}

function cerrarModalEvento() {
    const modal = document.getElementById('modal-evento-backdrop');
    if (modal) {
        modal.setAttribute('hidden', '');
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }
}

function regenerarFolioModal(e) {
    if (e) e.preventDefault();
    const num = Math.floor(100000 + Math.random() * 900000);
    const folio = 'EVT-' + num;
    const input = document.getElementById('input-modal-folio');
    const display = document.getElementById('display-modal-folio');
    if (input) input.value = folio;
    if (display) display.textContent = folio;
}
</script>
@endsection
