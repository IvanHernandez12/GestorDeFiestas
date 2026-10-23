@extends('layouts.app')

@section('content')
<a class="back-link" href="{{ route('eventos.dashboard') }}"><span aria-hidden="true">←</span> Eventos</a>
<section class="event-detail" data-event-detail-view data-event-id="{{ $eventoId }}" data-api-url="{{ url('/api/eventos') }}">
    <header class="event-heading">
        <div class="event-card__labels">
            <span class="badge badge--type" id="detail-type"></span>
            <span class="badge badge--state" id="detail-state"></span>
        </div>
        <h1 id="detail-name">Cargando evento…</h1>
        <p class="muted">Folio <span id="detail-folio"></span></p>
    </header>

    <div class="summary-grid">
        <x-summary-card icon="▣" label="Fecha" value="—" id="summary-date" />
        <x-summary-card icon="⌖" label="Ubicación" value="—" id="summary-location" />
        <x-summary-card icon="♧" label="Invitados" value="—" id="summary-guests" />
        <x-summary-card icon="$" label="Presupuesto" value="—" id="summary-budget" />
    </div>

    <nav class="tabs" aria-label="Apartados del evento" role="tablist">
        <button class="tab is-active" role="tab" aria-selected="true" aria-controls="panel-invitados" id="tab-invitados" data-tab="invitados"><span>♧</span> Invitados</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-salon" id="tab-salon" data-tab="salon"><span>▦</span> Salón</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-qr" id="tab-qr" data-tab="qr"><span>▧</span> QR</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-seguimiento" id="tab-seguimiento" data-tab="seguimiento"><span>♧</span> Seguimiento</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-evento" id="tab-evento" data-tab="evento"><span>▣</span> Evento</button>
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-info" id="tab-info" data-tab="info"><span>▤</span> Info</button>
    </nav>

    <section class="tab-panel" id="panel-invitados" role="tabpanel" aria-labelledby="tab-invitados" data-panel="invitados">
        <form class="guest-form panel-card" data-guest-form>
            <h2>Agregar invitado</h2>
            <div class="form-grid">
                <label>Nombre<input name="nombre" type="text" placeholder="Nombre" maxlength="120" required></label>
                <label>Correo<input name="correo" type="email" placeholder="correo@ejemplo.com" maxlength="160" required></label>
                <label>Categoría<select name="categoria" required><option>General</option><option>Familia</option><option>Amigos</option><option>Trabajo</option></select></label>
            </div>
            <button class="button button--primary" type="submit"><span aria-hidden="true">♧</span> Agregar</button>
            <p class="form-error" data-form-error hidden></p>
        </form>
        <div class="guest-list" data-guest-list>
            <div class="empty-state"><span class="empty-state__icon" aria-hidden="true">✉</span><p>Aún no hay invitados</p></div>
        </div>
    </section>

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
                <h2>Agregar</h2>
                <button class="tool-button" data-add-table>＋ Mesa</button>
                <div class="zone-tools"><select data-zone-type aria-label="Tipo de zona"><option>Escenario</option><option>Pista</option><option>Bar</option></select><button class="tool-button" data-add-zone>＋ Zona</button></div>
                <p class="muted tool-hint">Arrastra las mesas y zonas para acomodarlas. Las zonas reservadas sirven para escenario, pista y barra.</p>
            </aside>
            <div class="venue-canvas" data-venue-canvas aria-label="Croquis interactivo del salón"></div>
        </div>
    </section>

    <section class="tab-panel" id="panel-qr" role="tabpanel" aria-labelledby="tab-qr" data-panel="qr" hidden>
        <div class="panel-card qr-panel">
            <h2>Invitación QR</h2>
            <p class="muted">Comparte el acceso del evento con tus invitados.</p>
            <div class="qr-placeholder" role="img" aria-label="Vista previa del código QR"><span>QR</span></div>
            <p class="muted" id="qr-folio"></p>
        </div>
    </section>

    <section class="tab-panel" id="panel-seguimiento" role="tabpanel" aria-labelledby="tab-seguimiento" data-panel="seguimiento" hidden>
        <div class="provider-list">
            <article class="provider-card panel-card">
                <div class="provider-heading"><div><h2>Catering El Buen Tenedor</h2><p class="muted">Catering · $12,000</p></div><span class="status-pill">Pendiente</span></div>
                <div class="provider-fields">
                    <label>▣ Fecha estimada de entrega<input type="date" value="2026-10-30"></label>
                    <label>Estado de entrega<select><option>Pendiente</option><option>En proceso</option><option>Entregado</option></select></label>
                    <label>♧ Teléfono de contacto<input type="tel" value="+52 55 4567 8901"></label>
                    <label>✉ Correo de contacto<input type="email" value="pedidos@buentenedor.com"></label>
                </div>
            </article>
            <article class="provider-card panel-card">
                <div class="provider-heading"><div><h2>Transporte Elite</h2><p class="muted">Transporte · $6,000</p></div><span class="status-pill">Pendiente</span></div>
                <div class="provider-fields">
                    <label>▣ Fecha estimada de entrega<input type="date" value="2026-10-30"></label>
                    <label>Estado de entrega<select><option>Pendiente</option><option>En proceso</option><option>Entregado</option></select></label>
                    <label>♧ Teléfono de contacto<input type="tel" value="+52 55 8901 2345"></label>
                    <label>✉ Correo de contacto<input type="email" value="servicio@transporteelite.com"></label>
                </div>
            </article>
        </div>
    </section>

    <section class="tab-panel" id="panel-evento" role="tabpanel" aria-labelledby="tab-evento" data-panel="evento" hidden>
        <div class="panel-card album-card">
            <div class="album-heading"><h2><span aria-hidden="true">▣</span> Álbum del evento</h2><label class="button button--primary file-button"><span aria-hidden="true">▣</span> Tomar foto<input type="file" accept="image/*" capture="environment" data-photo-input hidden></label></div>
            <div class="photo-grid" data-photo-grid><div class="empty-state photo-empty"><span class="empty-state__icon" aria-hidden="true">▣</span><p>Aún no hay fotos</p><span>Toma la primera foto del evento.</span></div></div>
        </div>
    </section>

    <section class="tab-panel" id="panel-info" role="tabpanel" aria-labelledby="tab-info" data-panel="info" hidden>
        <div class="panel-card info-card">
            <div><span class="muted">Reglamento</span><p id="event-rules">—</p></div>
            <div><span class="muted">Prioridad de invitados</span><p id="event-priorities">—</p></div>
        </div>
    </section>
</section>
@endsection
