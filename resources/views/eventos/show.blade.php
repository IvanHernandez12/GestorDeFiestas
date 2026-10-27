@extends('layouts.app')

@section('content')
<a class="back-link" href="{{ route('eventos.dashboard') }}"><span aria-hidden="true">←</span> Volver a eventos</a>

<section class="event-detail" data-event-detail-view data-event-id="{{ $eventoId }}" data-api-url="{{ url('/api/eventos') }}" data-venue-layout="{{ json_encode(isset($evento) && $evento ? $evento->distribucion_salon : null) }}">
    <header class="event-heading-container">
        <div class="event-heading" style="margin-bottom:0;">
            <div class="event-card__labels">
                <span class="badge badge--type" id="detail-type">{{ isset($evento) && $evento ? $evento->tipo : '' }}</span>
                <span class="badge badge--state" id="detail-state">{{ isset($evento) && $evento ? $evento->estado : '' }}</span>
            </div>
            <h1 id="detail-name" style="margin: 8px 0 4px;">{{ isset($evento) && $evento ? $evento->nombre : 'Cargando evento…' }}</h1>
            <p class="muted">Folio <span id="detail-folio">{{ isset($evento) && $evento ? $evento->folio : '' }}</span></p>
        </div>

        <div class="event-heading-actions">
            <button type="button" class="button button--secondary button--sm" id="btn-editar-evento" onclick="abrirModalEditarEvento()">
                <span aria-hidden="true">✏</span> Editar información
            </button>
            <button type="button" class="button button--danger button--sm" id="btn-eliminar-evento" onclick="abrirModalEliminarDetalle()">
                <span aria-hidden="true">🗑</span> Eliminar evento
            </button>
        </div>
    </header>

    <div class="summary-grid">
        <x-summary-card icon="▣" label="Fecha" value="{{ isset($evento) && $evento && $evento->fecha_evento ? $evento->fecha_evento->format('d M Y') : '—' }}" id="summary-date" />
        <x-summary-card icon="⌖" label="Ubicación" value="{{ isset($evento) && $evento ? $evento->ubicacion : '—' }}" id="summary-location" />
        <x-summary-card icon="♧" label="Invitados" value="{{ isset($evento) && $evento ? ($evento->numero_invitados ?? 0) : '—' }}" id="summary-guests" />
        <x-summary-card icon="$" label="Presupuesto" value="{{ isset($evento) && $evento && $evento->presupuesto ? '$' . number_format($evento->presupuesto, 0) : '$0' }}" id="summary-budget" />
    </div>

    <!-- Navegación por pestañas del evento -->
    <nav class="tabs" aria-label="Apartados del evento" role="tablist">
        <button class="tab is-active" role="tab" aria-selected="true" aria-controls="panel-invitados" id="tab-invitados" data-tab="invitados"><span>♧</span> Invitados</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-salon" id="tab-salon" data-tab="salon"><span>▦</span> Salón</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-qr" id="tab-qr" data-tab="qr"><span>▧</span> QR</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-seguimiento" id="tab-seguimiento" data-tab="seguimiento"><span>♧</span> Seguimiento</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-evento" id="tab-evento" data-tab="evento"><span>▣</span> Evento</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-info" id="tab-info" data-tab="info"><span>▤</span> Info</button>
    </nav>

    <!-- 1. Panel de Invitados -->
    <section class="tab-panel" id="panel-invitados" role="tabpanel" aria-labelledby="tab-invitados" data-panel="invitados">
        <form class="guest-form panel-card" data-guest-form>
            <h2>Agregar comensal o invitado</h2>
            <div class="form-grid">
                <label>
                    Nombre
                    <input name="nombre" type="text" placeholder="Nombre completo" maxlength="60" required autocomplete="off">
                </label>
                <label>
                    Correo
                    <input name="correo" type="email" placeholder="correo@ejemplo.com" maxlength="160" required autocomplete="off">
                </label>
                <label>
                    Categoría
                    <select name="categoria" required>
                        <option>General</option>
                        <option>Familia</option>
                        <option>Amigos</option>
                        <option>Trabajo</option>
                    </select>
                </label>
            </div>
            <button class="button button--primary" type="submit"><span aria-hidden="true">♧</span> Agregar</button>
            <p class="form-error" data-form-error hidden></p>
        </form>
        <div class="guest-list" data-guest-list>
            <div class="empty-state"><span class="empty-state__icon" aria-hidden="true">✉</span><p>Aún no hay invitados</p></div>
        </div>
    </section>

    <!-- 2. Panel de Salón y Croquis con persistencia de lugares -->
    <section class="tab-panel" id="panel-salon" role="tabpanel" aria-labelledby="tab-salon" data-panel="salon" hidden>
        <div class="venue-layout">
            <aside class="venue-tools panel-card">
                <h2>Pincel de prioridad</h2>
                <p class="muted">Elige una prioridad y haz clic en las sillas para asignarla.</p>
                <div class="priority-list">
                    <button class="priority-option is-selected" data-priority="Familia"><i class="priority-dot priority-dot--family"></i> Familia</button>
                    <button class="priority-option" data-priority="Amigos"><i class="priority-dot priority-dot--friends"></i> Amigos</button>
                    <button class="priority-option" data-priority="Trabajo"><i class="priority-dot priority-dot--work"></i> Trabajo</button>
                    <button class="priority-option" data-priority="General"><i class="priority-dot priority-dot--general"></i> General</button>
                </div>
                <hr>
                <h2>Agregar elementos</h2>
                <label class="venue-control-label">
                    Forma de mesa
                    <select data-table-shape aria-label="Forma de mesa">
                        <option value="round">Redonda</option>
                        <option value="rectangular">Rectangular</option>
                    </select>
                </label>
                <label class="venue-control-label">
                    Sillas para la nueva mesa
                    <select data-chair-count aria-label="Sillas para la nueva mesa">
                        <option value="4">4 sillas</option>
                        <option value="6">6 sillas</option>
                        <option value="8" selected>8 sillas</option>
                        <option value="10">10 sillas</option>
                        <option value="12">12 sillas</option>
                    </select>
                </label>
                <button class="tool-button" data-add-table>＋ Mesa</button>
                <div class="zone-tools">
                    <select data-zone-type aria-label="Tipo de zona">
                        <option>Escenario</option>
                        <option>Pista</option>
                        <option>Bar</option>
                    </select>
                    <button class="tool-button" data-add-zone>＋ Zona</button>
                </div>
                <p class="muted tool-hint">Arrastra las mesas y zonas para acomodarlas. Las zonas reservadas sirven para escenario, pista y barra.</p>
                <hr>
                <!-- Botón de guardado de lugares -->
                <button type="button" class="button button--primary" data-save-venue style="width: 100%;">
                    <span aria-hidden="true">💾</span> Guardar lugares y croquis
                </button>
                <button type="button" class="button button--secondary" data-reset-venue style="width: 100%; margin-top: 8px;">
                    <span aria-hidden="true">↺</span> Restablecer salón
                </button>
                <span class="muted" data-venue-status style="display:block;text-align:center;margin-top:8px;font-size:11px;">
                    Lugares sincronizados con el servidor
                </span>
            </aside>
            <div class="venue-canvas" data-venue-canvas aria-label="Croquis interactivo del salón"></div>
        </div>
    </section>

    <!-- 3. Panel QR -->
    <section class="tab-panel" id="panel-qr" role="tabpanel" aria-labelledby="tab-qr" data-panel="qr" hidden>
        <div class="panel-card qr-panel">
            <h2>Invitación inteligente QR</h2>
            <p class="muted">Comparte el acceso seguro del evento con tus invitados.</p>
            <div class="qr-placeholder" role="img" aria-label="Vista previa del código QR"><span>QR</span></div>
            <p class="muted" id="qr-folio">Folio {{ isset($evento) && $evento ? $evento->folio : '' }}</p>
        </div>
    </section>

    <!-- 4. Panel de Seguimiento -->
    <section class="tab-panel" id="panel-seguimiento" role="tabpanel" aria-labelledby="tab-seguimiento" data-panel="seguimiento" hidden>
        <div class="provider-list" data-provider-tracking>
            <div class="empty-state"><p>Aún no has seleccionado proveedores para este evento.</p></div>
        </div>
    </section>

    <!-- 5. Panel de Evento / Álbum -->
    <section class="tab-panel" id="panel-evento" role="tabpanel" aria-labelledby="tab-evento" data-panel="evento" hidden>
        <div class="panel-card album-card">
            <div class="album-heading">
                <h2><span aria-hidden="true">▣</span> Álbum del evento</h2>
                <label class="button button--primary file-button"><span aria-hidden="true">▣</span> Tomar foto<input type="file" accept="image/*" capture="environment" data-photo-input hidden></label>
            </div>
            <div class="photo-grid" data-photo-grid><div class="empty-state photo-empty"><span class="empty-state__icon" aria-hidden="true">▣</span><p>Aún no hay fotos</p><span>Toma la primera foto del evento.</span></div></div>
        </div>
    </section>

    <!-- 6. Panel de Info -->
    <section class="tab-panel" id="panel-info" role="tabpanel" aria-labelledby="tab-info" data-panel="info" hidden>
        <div class="panel-card info-card">
            <div><span class="muted">Reglamento y Observaciones</span><p id="event-rules">{{ isset($evento) && $evento && $evento->reglamento ? $evento->reglamento : 'Sin reglamento registrado' }}</p></div>
            <div><span class="muted">Prioridad de comensales</span><p id="event-priorities">Por categorías (Familia, Amigos, Trabajo, General)</p></div>
            <div>
                <button type="button" class="button button--secondary button--sm" onclick="abrirModalEditarEvento()">
                    <span aria-hidden="true">✏</span> Modificar información general
                </button>
            </div>
        </div>
    </section>

</section>

<!-- Modal de Edición de Evento -->
<div class="modal-backdrop" id="modal-editar-evento" hidden onclick="if(event.target === this) cerrarModalEditarEvento()">
    <div class="modal-dialog panel-card" role="dialog" aria-modal="true" aria-labelledby="modal-editar-titulo" style="max-width:660px;">
        <header class="modal-header">
            <div>
                <span class="hero-kicker modal-kicker"><span aria-hidden="true">✏</span> Edición de evento</span>
                <h2 id="modal-editar-titulo">Modificar información del evento</h2>
                <p class="muted">Actualiza los datos del evento en la base de datos corporativa.</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModalEditarEvento()" aria-label="Cerrar">✕</button>
        </header>

        <form id="form-editar-evento" onsubmit="guardarEdicionEvento(event)">
            @csrf
            <div class="form-grid-modal">
                <label>
                    <span>Nombre del evento <b class="required">*</b></span>
                    <input name="nombre" id="edit-nombre" type="text" maxlength="60" required value="{{ isset($evento) && $evento ? $evento->nombre : '' }}">
                </label>

                <label>
                    <span>Tipo de evento <b class="required">*</b></span>
                    <select name="tipo" id="edit-tipo" required>
                        <option value="Boda" {{ isset($evento) && $evento && $evento->tipo == 'Boda' ? 'selected' : '' }}>Boda</option>
                        <option value="XV Años" {{ isset($evento) && $evento && $evento->tipo == 'XV Años' ? 'selected' : '' }}>XV Años</option>
                        <option value="Cumpleaños" {{ isset($evento) && $evento && $evento->tipo == 'Cumpleaños' ? 'selected' : '' }}>Cumpleaños</option>
                        <option value="Graduación" {{ isset($evento) && $evento && $evento->tipo == 'Graduación' ? 'selected' : '' }}>Graduación</option>
                        <option value="Bautizo" {{ isset($evento) && $evento && $evento->tipo == 'Bautizo' ? 'selected' : '' }}>Bautizo</option>
                        <option value="Corporativo" {{ isset($evento) && $evento && $evento->tipo == 'Corporativo' ? 'selected' : '' }}>Corporativo</option>
                        <option value="Reunión Social" {{ isset($evento) && $evento && $evento->tipo == 'Reunión Social' ? 'selected' : '' }}>Reunión Social</option>
                    </select>
                </label>

                <label>
                    <span>Estado del evento <b class="required">*</b></span>
                    <select name="estado" id="edit-estado" required>
                        <option value="Planificando" {{ isset($evento) && $evento && $evento->estado == 'Planificando' ? 'selected' : '' }}>Planificando</option>
                        <option value="Confirmado" {{ isset($evento) && $evento && $evento->estado == 'Confirmado' ? 'selected' : '' }}>Confirmado</option>
                        <option value="En curso" {{ isset($evento) && $evento && $evento->estado == 'En curso' ? 'selected' : '' }}>En curso</option>
                        <option value="Finalizado" {{ isset($evento) && $evento && $evento->estado == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                        <option value="Cancelado" {{ isset($evento) && $evento && $evento->estado == 'Cancelado' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </label>

                <label>
                    <span>Fecha del evento <b class="required">*</b></span>
                    <input name="fecha_evento" id="edit-fecha" type="date" required value="{{ isset($evento) && $evento && $evento->fecha_evento ? $evento->fecha_evento->format('Y-m-d') : '' }}">
                </label>

                <label>
                    <span>Ubicación o Recinto <b class="required">*</b></span>
                    <input name="ubicacion" id="edit-ubicacion" type="text" maxlength="80" required value="{{ isset($evento) && $evento ? $evento->ubicacion : '' }}">
                </label>

                <label>
                    <span>Número estimado de invitados</span>
                    <input name="numero_invitados" id="edit-invitados" type="text" inputmode="numeric" placeholder="30" value="{{ isset($evento) && $evento ? $evento->numero_invitados : '30' }}">
                </label>

                <label>
                    <span>Presupuesto planeado ($ MXN)</span>
                    <input name="presupuesto" id="edit-presupuesto" type="text" inputmode="decimal" placeholder="100000" value="{{ isset($evento) && $evento ? $evento->presupuesto : '100000' }}">
                </label>
            </div>

            <label class="form-row-full">
                <span>Reglamento / Observaciones</span>
                <input name="reglamento" id="edit-reglamento" type="text" maxlength="250" value="{{ isset($evento) && $evento ? $evento->reglamento : '' }}" placeholder="ej. Código de vestimenta formal, acceso con QR">
            </label>

            <p class="form-error" id="edit-form-error" hidden></p>

            <footer class="modal-footer">
                <button type="button" class="button button--secondary" onclick="cerrarModalEditarEvento()">Cancelar</button>
                <button type="submit" class="button button--primary" id="btn-guardar-edicion">
                    <span aria-hidden="true">💾</span> Guardar modificaciones
                </button>
            </footer>
        </form>
    </div>
</div>

<!-- Modal de Eliminación de Evento (Vista Detalle) -->
<div class="modal-backdrop" id="modal-eliminar-detalle" hidden onclick="if(event.target === this) cerrarModalEliminarDetalle()">
    <div class="modal-dialog panel-card" role="dialog" aria-modal="true" aria-labelledby="modal-eliminar-detalle-titulo" style="max-width:480px;">
        <header class="modal-header">
            <div>
                <span class="hero-kicker modal-kicker" style="color:#DC2626;border-color:#FCA5A5;background:#FEF2F2;">
                    <span aria-hidden="true">⚠️</span> Confirmar eliminación
                </span>
                <h2 id="modal-eliminar-detalle-titulo" style="margin-top:8px;color:#991B1B;">¿Eliminar este evento?</h2>
                <p class="muted">Esta acción es permanente y no se puede deshacer.</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModalEliminarDetalle()" aria-label="Cerrar">✕</button>
        </header>

        <div style="padding:10px 0 16px;">
            <p style="margin:0 0 10px;font-size:13px;color:#374151;line-height:1.5;">
                Estás a punto de borrar definitivamente este evento del sistema:
            </p>
            <div style="padding:12px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;">
                <strong id="delete-detalle-nombre" style="font-size:15px;color:#0F172A;">{{ isset($evento) && $evento ? $evento->nombre : '' }}</strong>
            </div>
            <p style="margin:12px 0 0;font-size:12px;color:#64748B;">
                Se borrarán también todos los comensales asociados y su distribución de lugares.
            </p>
        </div>

        <footer class="modal-footer">
            <button type="button" class="button button--secondary" onclick="cerrarModalEliminarDetalle()">Cancelar</button>
            <button type="button" class="button button--danger" id="btn-confirmar-eliminar-detalle" onclick="ejecutarEliminarDetalle()">
                <span aria-hidden="true">🗑</span> Sí, eliminar evento
            </button>
        </footer>
    </div>
</div>

<script>
function abrirModalEditarEvento() {
    const modal = document.getElementById('modal-editar-evento');
    const error = document.getElementById('edit-form-error');
    if (error) { error.hidden = true; error.textContent = ''; }
    modal?.removeAttribute('hidden');
    modal?.classList.add('is-open');
    document.body.style.overflow = 'hidden';
}

function cerrarModalEditarEvento() {
    const modal = document.getElementById('modal-editar-evento');
    modal?.setAttribute('hidden', '');
    modal?.classList.remove('is-open');
    document.body.style.overflow = '';
}

async function guardarEdicionEvento(event) {
    event.preventDefault();
    const root = document.querySelector('[data-event-detail-view]');
    const error = document.getElementById('edit-form-error');
    const button = document.getElementById('btn-guardar-edicion');
    const payload = Object.fromEntries(new FormData(event.currentTarget).entries());
    payload.numero_invitados = Number.parseInt(payload.numero_invitados, 10);
    payload.presupuesto = Number.parseFloat(payload.presupuesto);
    button.disabled = true;
    try {
        const response = await fetch(`${root.dataset.apiUrl}/${root.dataset.eventId}`, {
            method: 'PUT',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? result.message ?? 'Error al guardar modificaciones.');
        window.location.reload();
    } catch (exception) {
        error.textContent = exception.message;
        error.hidden = false;
    } finally {
        button.disabled = false;
    }
}

function abrirModalEliminarDetalle() {
    const modal = document.getElementById('modal-eliminar-detalle');
    modal?.removeAttribute('hidden');
    modal?.classList.add('is-open');
    document.body.style.overflow = 'hidden';
}

function cerrarModalEliminarDetalle() {
    const modal = document.getElementById('modal-eliminar-detalle');
    modal?.setAttribute('hidden', '');
    modal?.classList.remove('is-open');
    document.body.style.overflow = '';
}

async function ejecutarEliminarDetalle() {
    const root = document.querySelector('[data-event-detail-view]');
    const button = document.getElementById('btn-confirmar-eliminar-detalle');
    button.disabled = true;
    try {
        const response = await fetch(`${root.dataset.apiUrl}/${root.dataset.eventId}`, { method: 'DELETE', headers: { Accept: 'application/json' } });
        if (!response.ok && response.status !== 204) throw new Error('No se pudo eliminar el evento.');
        window.location.assign("{{ route('eventos.dashboard') }}");
    } catch (exception) {
        window.alert(exception.message);
        button.disabled = false;
    }
}
</script>
@endsection
