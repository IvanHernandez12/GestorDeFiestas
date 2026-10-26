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

<!-- Modal de confirmación tras crear evento -->
<div class="modal-backdrop" id="modal-confirmacion-backdrop" hidden>
    <div class="modal-dialog panel-card" role="dialog" aria-modal="true" aria-labelledby="modal-confirmacion-titulo" style="max-width:520px;">
        <header class="modal-header">
            <div>
                <span class="hero-kicker modal-kicker" style="color:#15803d;border-color:#bbf7d0;background:#f0fdf4;">
                    <span aria-hidden="true">✓</span> Evento registrado
                </span>
                <h2 id="modal-confirmacion-titulo" style="margin-top:8px;">¡Evento iniciado exitosamente!</h2>
                <p class="muted">El evento ha sido guardado en la base de datos corporativa.</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarConfirmacion()" aria-label="Cerrar">✕</button>
        </header>

        <div id="confirmacion-resumen" style="display:grid;gap:10px;padding:4px 0 18px;">
            <!-- Rellenado dinámicamente por JS -->
        </div>

        <footer class="modal-footer">
            <button type="button" class="button button--primary" onclick="cerrarConfirmacion()">
                <span aria-hidden="true">✓</span> Entendido, ir al dashboard
            </button>
        </footer>
    </div>
</div>

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
/* ── Helpers ── */
function fmtFecha(valor) {
    if (!valor) return '—';
    return new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' })
        .format(new Date(String(valor).slice(0, 10) + 'T12:00:00Z'));
}
function fmtDinero(valor) {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(Number(valor) || 0);
}

/* ── Folio ── */
function regenerarFolioModal(e) {
    if (e) e.preventDefault();
    const num = Math.floor(100000 + Math.random() * 900000);
    const folio = 'EVT-' + num;
    const input = document.getElementById('input-modal-folio');
    const display = document.getElementById('display-modal-folio');
    if (input) input.value = folio;
    if (display) display.textContent = folio;
}

/* ── Modal de alta ── */
function abrirModalEvento() {
    const modal = document.getElementById('modal-evento-backdrop');
    if (!modal) return;
    regenerarFolioModal();
    // Limpiar errores anteriores
    const err = modal.querySelector('[data-event-form-error]');
    if (err) { err.hidden = true; err.textContent = ''; }
    modal.removeAttribute('hidden');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    setTimeout(function() {
        const input = document.getElementById('input-nombre-evento');
        if (input) input.focus();
    }, 50);
}

function cerrarModalEvento() {
    const modal = document.getElementById('modal-evento-backdrop');
    if (!modal) return;
    modal.setAttribute('hidden', '');
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
    const form = modal.querySelector('[data-event-create-form]');
    if (form) form.reset();
}

/* ── Modal de confirmación ── */
function mostrarConfirmacion(evento) {
    const resumen = document.getElementById('confirmacion-resumen');
    if (!resumen) return;

    const filas = [
        { icon: '', label: 'Folio',          valor: evento.folio },
        { icon: '', label: 'Evento',          valor: evento.nombre },
        { icon: '', label: 'Tipo',            valor: evento.tipo },
        { icon: '', label: 'Fecha',          valor: fmtFecha(evento.fecha_evento) },
        { icon: '', label: 'Recinto',         valor: evento.ubicacion },
        { icon: '', label: 'Invitados',       valor: evento.numero_invitados ?? 0 },
        { icon: '', label: 'Presupuesto',     valor: fmtDinero(evento.presupuesto) },
        { icon: '', label: 'Estado',          valor: evento.estado ?? 'Planificando' },
    ];

    resumen.innerHTML = filas.map(function(f) {
        return '<div style="display:flex;align-items:center;gap:12px;padding:9px 12px;background:#F8FAFC;border-radius:8px;border:1px solid #E5E7EB;">' +
            '<span style="width:22px;text-align:center;color:#3B82F6;" aria-hidden="true">' + f.icon + '</span>' +
            '<span style="flex:1;font-size:12px;color:#64748B;">' + f.label + '</span>' +
            '<strong style="font-size:13px;color:#111827;">' + f.valor + '</strong>' +
        '</div>';
    }).join('');

    const modal = document.getElementById('modal-confirmacion-backdrop');
    if (modal) {
        modal.removeAttribute('hidden');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarConfirmacion() {
    const modal = document.getElementById('modal-confirmacion-backdrop');
    if (modal) {
        modal.setAttribute('hidden', '');
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }
    actualizarDashboard();
}

/* ── Actualizar dashboard (tarjetas + métricas) ── */
function actualizarDashboard() {
    const apiUrl = '{{ url("/api/eventos") }}';
    fetch(apiUrl, { headers: { Accept: 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            const eventos = resp.data ?? resp;

            // Actualizar métricas
            const statEventos = document.getElementById('stat-active-events');
            const statGuests  = document.getElementById('stat-total-guests');
            if (statEventos) statEventos.textContent = eventos.length + ' eventos';
            if (statGuests) {
                const total = eventos.reduce(function(s, e) { return s + (parseInt(e.numero_invitados, 10) || 0); }, 0);
                statGuests.textContent = total + ' comensales';
            }

            // Actualizar contador de sección
            const countLabel = document.querySelector('[data-event-count]');
            if (countLabel) countLabel.textContent = eventos.length + ' eventos';

            // Re-renderizar tarjetas
            const container = document.querySelector('[data-events-container]');
            const template  = document.getElementById('event-card-template');
            if (!container || !template) return;

            container.replaceChildren();
            if (!eventos.length) {
                container.innerHTML = '<p class="loading-state">Aún no hay eventos registrados.</p>';
                return;
            }
            eventos.forEach(function(evento) {
                const card = template.content.firstElementChild.cloneNode(true);
                card.querySelector('[data-event-type]').textContent     = evento.tipo;
                card.querySelector('[data-event-state]').textContent    = evento.estado;
                card.querySelector('[data-event-name]').textContent     = evento.nombre;
                card.querySelector('[data-event-date]').textContent     = fmtFecha(evento.fecha_evento);
                card.querySelector('[data-event-location]').textContent = evento.ubicacion;
                card.querySelector('[data-event-guests]').textContent   = evento.numero_invitados ?? 0;
                const budget = card.querySelector('[data-event-budget]');
                if (budget) budget.textContent = fmtDinero(evento.presupuesto);
                card.querySelector('[data-event-folio]').textContent    = 'Folio ' + evento.folio;
                card.querySelector('[data-event-detail]').href          = '/eventos/' + evento.id;
                container.append(card);
            });
        })
        .catch(function() {
            // Si falla el fetch, dejamos las tarjetas actuales sin cambio
        });
}

/* ── Intercepción del submit del formulario ── */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-event-create-form]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const errorBox = form.querySelector('[data-event-form-error]');
        if (errorBox) { errorBox.hidden = true; errorBox.textContent = ''; }

        const formData = new FormData(form);
        const datos = Object.fromEntries(formData.entries());
        if (datos.numero_invitados) datos.numero_invitados = parseInt(datos.numero_invitados, 10);
        if (datos.presupuesto)      datos.presupuesto      = parseFloat(datos.presupuesto);

        const submitBtn = document.getElementById('btn-confirmar-creacion');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span aria-hidden="true">⏳</span> Registrando…';
        }

        fetch('{{ url("/api/eventos") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(datos),
        })
        .then(function(r) {
            if (!r.ok) {
                return r.json().then(function(body) {
                    const msg = Object.values(body.errors ?? {}).flat()[0] ?? body.message ?? 'Error al registrar.';
                    throw new Error(msg);
                });
            }
            return r.json();
        })
        .then(function(eventoCreado) {
            cerrarModalEvento();
            mostrarConfirmacion(eventoCreado);
        })
        .catch(function(err) {
            if (errorBox) {
                errorBox.textContent = err.message;
                errorBox.hidden = false;
            }
        })
        .finally(function() {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span aria-hidden="true">＋</span> Guardar y comenzar evento';
            }
        });
    });

    // Cargar dashboard inicial con datos frescos de la API
    actualizarDashboard();
});
</script>
@endsection
