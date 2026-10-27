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
        <button class="tab" role="tab" aria-selected="false" aria-controls="panel-proveedores" id="tab-proveedores" data-tab="proveedores" style="font-weight:700;color:#2563EB;"><span>★</span> Proveedores</button>
    </nav>

    <!-- 1. Panel de Invitados -->
    <section class="tab-panel" id="panel-invitados" role="tabpanel" aria-labelledby="tab-invitados" data-panel="invitados">
        <form class="guest-form panel-card" data-guest-form>
            <h2>Agregar comensal o invitado</h2>
            <div class="form-grid">
                <label>
                    Nombre
                    <input name="nombre" type="text" placeholder="Nombre completo" maxlength="120" required autocomplete="off">
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
        <div class="provider-list">
            <article class="provider-card panel-card">
                <div class="provider-heading"><div><h2>Catering El Buen Tenedor</h2><p class="muted">Catering · $12,000</p></div><span class="status-pill">Pendiente</span></div>
                <div class="provider-fields">
                    <label>▣ Fecha estimada de entrega<input type="date" value="{{ isset($evento) && $evento && $evento->fecha_evento ? $evento->fecha_evento->format('Y-m-d') : date('Y-m-d') }}"></label>
                    <label>Estado de entrega<select><option>Pendiente</option><option>En proceso</option><option>Entregado</option></select></label>
                    <label>♧ Teléfono de contacto<input type="tel" value="+52 55 4567 8901"></label>
                    <label>✉ Correo de contacto<input type="email" value="pedidos@buentenedor.com"></label>
                </div>
            </article>
            <article class="provider-card panel-card">
                <div class="provider-heading"><div><h2>Transporte Elite</h2><p class="muted">Transporte · $6,000</p></div><span class="status-pill">Pendiente</span></div>
                <div class="provider-fields">
                    <label>▣ Fecha estimada de entrega<input type="date" value="{{ isset($evento) && $evento && $evento->fecha_evento ? $evento->fecha_evento->format('Y-m-d') : date('Y-m-d') }}"></label>
                    <label>Estado de entrega<select><option>Pendiente</option><option>En proceso</option><option>Entregado</option></select></label>
                    <label>♧ Teléfono de contacto<input type="tel" value="+52 55 8901 2345"></label>
                    <label>✉ Correo de contacto<input type="email" value="servicio@transporteelite.com"></label>
                </div>
            </article>
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

    <!-- 7. NUEVO PANEL: Proveedores Recomendados (SaaS Carousel) -->
    <section class="tab-panel" id="panel-proveedores" role="tabpanel" aria-labelledby="tab-proveedores" data-panel="proveedores" hidden>
        <div class="providers-hero">
            <div>
                <span class="saas-badge"><span aria-hidden="true">★</span> Red de Proveedores Oficiales</span>
                <h2>Alternativas de Proveedores para tu Fiesta</h2>
                <p>Monetización y catálogo de proveedores verificados por Bigbitbyte. Encuentra paquetes con descuento y cotiza en línea sin salir de tu panel.</p>
            </div>
            <div style="text-align:right;">
                <span style="display:block;font-size:26px;font-weight:700;">5</span>
                <span style="font-size:11px;color:#DBEAFE;text-transform:uppercase;letter-spacing:0.5px;">Aliados Activos</span>
            </div>
        </div>

        <div class="carousel-viewport">
            <div class="carousel-track" id="providers-carousel-track">
                <!-- Proveedor 1: Banquetes Gourmet -->
                <article class="carousel-card">
                    <div class="carousel-card__media">
                        <img class="carousel-card__img" src="{{ asset('images/proveedores/catering.jpg') }}" alt="Banquetes Imperial Gourmet" loading="lazy">
                        <span class="carousel-card__category">Banquete & Catering</span>
                        <span class="carousel-card__verified">✓ Verificado</span>
                    </div>
                    <div class="carousel-card__body">
                        <h3 class="carousel-card__title">Banquetes Imperial Gourmet</h3>
                        <p class="carousel-card__desc">Menús de gala de 3 y 4 tiempos, barras de mixología de autor, mesas de quesos artesanales y meseros de etiqueta.</p>
                        <div class="carousel-card__footer">
                            <div class="carousel-card__price-box">
                                <span class="carousel-card__price-label">Tarifa estimada</span>
                                <span class="carousel-card__price">Desde $350 / pers.</span>
                            </div>
                            <button type="button" class="button button--primary button--sm" onclick="solicitarCotizacion('Banquetes Imperial Gourmet', 'Banquete & Catering')">
                                Solicitar
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Proveedor 2: Mobiliario Lounge -->
                <article class="carousel-card">
                    <div class="carousel-card__media">
                        <img class="carousel-card__img" src="{{ asset('images/proveedores/mobiliario.jpg') }}" alt="Lounge & Decor Elegance" loading="lazy">
                        <span class="carousel-card__category">Mobiliario & Carpas</span>
                        <span class="carousel-card__verified">✓ Verificado</span>
                    </div>
                    <div class="carousel-card__body">
                        <h3 class="carousel-card__title">Lounge & Decor Elegance</h3>
                        <p class="carousel-card__desc">Salas lounge de terciopelo, sillas Tiffany y Crossback, mesas periqueras, carpas panorámicas y ambientación cálida.</p>
                        <div class="carousel-card__footer">
                            <div class="carousel-card__price-box">
                                <span class="carousel-card__price-label">Paquetes desde</span>
                                <span class="carousel-card__price">$14,500 MXN</span>
                            </div>
                            <button type="button" class="button button--primary button--sm" onclick="solicitarCotizacion('Lounge & Decor Elegance', 'Mobiliario & Carpas')">
                                Solicitar
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Proveedor 3: Audio & DJ -->
                <article class="carousel-card">
                    <div class="carousel-card__media">
                        <img class="carousel-card__img" src="{{ asset('images/proveedores/audio_dj.jpg') }}" alt="DJ Nova & Robotic Stage" loading="lazy">
                        <span class="carousel-card__category">Audio, DJ & Iluminación</span>
                        <span class="carousel-card__verified">✓ Verificado</span>
                    </div>
                    <div class="carousel-card__body">
                        <h3 class="carousel-card__title">DJ Nova & Robotic Stage</h3>
                        <p class="carousel-card__desc">Audio lineal QSC de alta fidelidad, cabinas DJ con pantallas LED pixel, luces robóticas Beam y efectos especiales.</p>
                        <div class="carousel-card__footer">
                            <div class="carousel-card__price-box">
                                <span class="carousel-card__price-label">Shows desde</span>
                                <span class="carousel-card__price">$18,000 MXN</span>
                            </div>
                            <button type="button" class="button button--primary button--sm" onclick="solicitarCotizacion('DJ Nova & Robotic Stage', 'Audio, DJ & Iluminación')">
                                Solicitar
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Proveedor 4: Repostería -->
                <article class="carousel-card">
                    <div class="carousel-card__media">
                        <img class="carousel-card__img" src="{{ asset('images/proveedores/reposteria.jpg') }}" alt="Sweet Dreams Pastelería Fina" loading="lazy">
                        <span class="carousel-card__category">Pastelería & Mesa Dulce</span>
                        <span class="carousel-card__verified">✓ Verificado</span>
                    </div>
                    <div class="carousel-card__body">
                        <h3 class="carousel-card__title">Sweet Dreams Pastelería Fina</h3>
                        <p class="carousel-card__desc">Pasteles de autor en múltiples niveles con detalles en hoja de oro comestible, mesas de postres gourmet y repostería fina.</p>
                        <div class="carousel-card__footer">
                            <div class="carousel-card__price-box">
                                <span class="carousel-card__price-label">Diseños desde</span>
                                <span class="carousel-card__price">$6,500 MXN</span>
                            </div>
                            <button type="button" class="button button--primary button--sm" onclick="solicitarCotizacion('Sweet Dreams Pastelería Fina', 'Pastelería & Mesa Dulce')">
                                Solicitar
                            </button>
                        </div>
                    </div>
                </article>

                <!-- Proveedor 5: Fotografía y Video -->
                <article class="carousel-card">
                    <div class="carousel-card__media">
                        <img class="carousel-card__img" src="{{ asset('images/proveedores/fotografia.jpg') }}" alt="Lumiere Foto & Cinema 4K" loading="lazy">
                        <span class="carousel-card__category">Foto & Video 4K</span>
                        <span class="carousel-card__verified">✓ Verificado</span>
                    </div>
                    <div class="carousel-card__body">
                        <h3 class="carousel-card__title">Lumiere Foto & Cinema 4K</h3>
                        <p class="carousel-card__desc">Cobertura fotográfica y cinematográfica 4K con dron, dos fotógrafos en piso, photobook encuadernado y entrega digital.</p>
                        <div class="carousel-card__footer">
                            <div class="carousel-card__price-box">
                                <span class="carousel-card__price-label">Cobertura desde</span>
                                <span class="carousel-card__price">$22,000 MXN</span>
                            </div>
                            <button type="button" class="button button--primary button--sm" onclick="solicitarCotizacion('Lumiere Foto & Cinema 4K', 'Foto & Video 4K')">
                                Solicitar
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Controles del carrusel -->
            <div class="carousel-controls">
                <button type="button" class="carousel-nav-btn" id="btn-carousel-prev" aria-label="Anterior proveedor" onclick="moverCarrusel(-1)">‹</button>
                <div style="font-size:12px;color:#64748B;" id="carousel-page-indicator">5 opciones disponibles para tu fiesta</div>
                <button type="button" class="carousel-nav-btn" id="btn-carousel-next" aria-label="Siguiente proveedor" onclick="moverCarrusel(1)">›</button>
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
                    <input name="nombre" id="edit-nombre" type="text" maxlength="120" required value="{{ isset($evento) && $evento ? $evento->nombre : '' }}">
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
                    <input name="ubicacion" id="edit-ubicacion" type="text" maxlength="160" required value="{{ isset($evento) && $evento ? $evento->ubicacion : '' }}">
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
                <input name="reglamento" id="edit-reglamento" type="text" maxlength="500" value="{{ isset($evento) && $evento ? $evento->reglamento : '' }}" placeholder="ej. Código de vestimenta formal, acceso con QR">
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

<!-- Modal para Solicitar Cotización de Proveedor SaaS -->
<div class="modal-backdrop" id="modal-cotizar-proveedor" hidden onclick="if(event.target === this) cerrarModalCotizar()">
    <div class="modal-dialog panel-card" role="dialog" aria-modal="true" aria-labelledby="modal-cotizar-titulo" style="max-width:520px;">
        <header class="modal-header">
            <div>
                <span class="hero-kicker modal-kicker" style="color:#1D4ED8;border-color:#BFDBFE;background:#EFF6FF;">
                    <span aria-hidden="true">★</span> Alianza Comercial SaaS
                </span>
                <h2 id="modal-cotizar-titulo" style="margin-top:8px;">Solicitar cotización</h2>
                <p class="muted">Conecta directamente con nuestros proveedores aliados para tu evento.</p>
            </div>
            <button type="button" class="modal-close" onclick="cerrarModalCotizar()" aria-label="Cerrar">✕</button>
        </header>

        <form id="form-cotizar-proveedor" onsubmit="enviarSolicitudProveedor(event)">
            <div style="padding:4px 0 16px;display:grid;gap:12px;">
                <div style="padding:12px;background:#F0F9FF;border:1px solid #BFDBFE;border-radius:8px;">
                    <span class="muted" style="display:block;font-size:11px;">PROVEEDOR SELECCIONADO</span>
                    <strong id="cotizar-nombre-proveedor" style="font-size:16px;color:#1E3A8A;">Banquetes Imperial Gourmet</strong>
                    <span id="cotizar-categoria-proveedor" class="badge" style="margin-top:6px;display:inline-block;background:#DBEAFE;color:#1D4ED8;">Banquete</span>
                </div>

                <label style="display:grid;gap:6px;font-size:12px;color:#374151;">
                    <span>Fecha requerida del servicio</span>
                    <input type="date" id="cotizar-fecha" value="{{ isset($evento) && $evento && $evento->fecha_evento ? $evento->fecha_evento->format('Y-m-d') : date('Y-m-d') }}">
                </label>

                <label style="display:grid;gap:6px;font-size:12px;color:#374151;">
                    <span>Número aproximado de comensales / invitados</span>
                    <input type="number" id="cotizar-invitados" value="{{ isset($evento) && $evento ? ($evento->numero_invitados ?? 30) : 30 }}" min="1">
                </label>

                <label style="display:grid;gap:6px;font-size:12px;color:#374151;">
                    <span>Requerimientos especiales o especificaciones</span>
                    <textarea id="cotizar-detalles" rows="3" style="width:100%;padding:8px 12px;border:1px solid #C9D9EA;border-radius:7px;font-family:inherit;font-size:14px;" placeholder="ej. Menú de 3 tiempos, servicio de meseros de gala..."></textarea>
                </label>
            </div>

            <footer class="modal-footer">
                <button type="button" class="button button--secondary" onclick="cerrarModalCotizar()">Cancelar</button>
                <button type="submit" class="button button--primary" id="btn-enviar-cotizacion">
                    <span aria-hidden="true">✉</span> Enviar solicitud al proveedor
                </button>
            </footer>
        </form>
    </div>
</div>

<script>
/* ── Control del Carrusel de Proveedores ── */
function moverCarrusel(direccion) {
    const track = document.getElementById('providers-carousel-track');
    if (!track) return;
    const cardWidth = track.firstElementChild ? track.firstElementChild.offsetWidth + 18 : 320;
    track.scrollBy({ left: direccion * cardWidth, behavior: 'smooth' });
}

/* ── Modal de Cotización a Proveedor ── */
function solicitarCotizacion(nombre, categoria) {
    document.getElementById('cotizar-nombre-proveedor').textContent = nombre;
    document.getElementById('cotizar-categoria-proveedor').textContent = categoria;
    const modal = document.getElementById('modal-cotizar-proveedor');
    if (modal) {
        modal.removeAttribute('hidden');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarModalCotizar() {
    const modal = document.getElementById('modal-cotizar-proveedor');
    if (modal) {
        modal.setAttribute('hidden', '');
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }
}

function enviarSolicitudProveedor(e) {
    e.preventDefault();
    const nombre = document.getElementById('cotizar-nombre-proveedor').textContent;
    const btn = document.getElementById('btn-enviar-cotizacion');
    btn.disabled = true;
    btn.innerHTML = '<span aria-hidden="true">⏳</span> Enviando…';

    setTimeout(function() {
        btn.disabled = false;
        btn.innerHTML = '<span aria-hidden="true">✉</span> Enviar solicitud al proveedor';
        cerrarModalCotizar();
        if (typeof showToast === 'function') {
            showToast('¡Solicitud enviada con éxito a ' + nombre + '! Un asesor te contactará en breve.');
        } else {
            alert('¡Solicitud enviada con éxito a ' + nombre + '!');
        }
    }, 600);
}

/* ── Modal de Edición de Evento ── */
function abrirModalEditarEvento() {
    const modal = document.getElementById('modal-editar-evento');
    if (!modal) return;
    const err = document.getElementById('edit-form-error');
    if (err) { err.hidden = true; err.textContent = ''; }
    modal.removeAttribute('hidden');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
}

function cerrarModalEditarEvento() {
    const modal = document.getElementById('modal-editar-evento');
    if (!modal) return;
    modal.setAttribute('hidden', '');
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
}

async function guardarEdicionEvento(e) {
    e.preventDefault();
    const form = e.target;
    const root = document.querySelector('[data-event-detail-view]');
    const errBox = document.getElementById('edit-form-error');
    const btn = document.getElementById('btn-guardar-edicion');

    const payload = {
        nombre: document.getElementById('edit-nombre').value.trim(),
        tipo: document.getElementById('edit-tipo').value,
        estado: document.getElementById('edit-estado').value,
        fecha_evento: document.getElementById('edit-fecha').value,
        ubicacion: document.getElementById('edit-ubicacion').value.trim(),
        numero_invitados: parseInt(document.getElementById('edit-invitados').value, 10) || 0,
        presupuesto: parseFloat(document.getElementById('edit-presupuesto').value) || 0,
        reglamento: document.getElementById('edit-reglamento').value.trim()
    };

    btn.disabled = true;
    btn.innerHTML = '<span aria-hidden="true">⏳</span> Guardando…';

    try {
        const resp = await fetch(`${root.dataset.apiUrl}/${root.dataset.eventId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payload)
        });

        if (!resp.ok) {
            const data = await resp.json().catch(() => ({}));
            throw new Error(data.message || 'Error al guardar modificaciones.');
        }

        const actualizado = await resp.json();

        // Actualizar vista en vivo
        document.getElementById('detail-name').textContent = actualizado.nombre;
        document.getElementById('detail-type').textContent = actualizado.tipo;
        document.getElementById('detail-state').textContent = actualizado.estado;
        document.getElementById('summary-location').textContent = actualizado.ubicacion;
        document.getElementById('summary-guests').textContent = actualizado.numero_invitados;
        document.getElementById('summary-budget').textContent = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(actualizado.presupuesto);
        document.getElementById('event-rules').textContent = actualizado.reglamento || 'Sin reglamento registrado';
        document.getElementById('delete-detalle-nombre').textContent = actualizado.nombre;

        cerrarModalEditarEvento();
        if (typeof showToast === 'function') {
            showToast('¡Información del evento actualizada correctamente!');
        }
    } catch (err) {
        if (errBox) {
            errBox.textContent = err.message;
            errBox.hidden = false;
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span aria-hidden="true">💾</span> Guardar modificaciones';
    }
}

/* ── Modal de Eliminación de Evento (Detalle) ── */
function abrirModalEliminarDetalle() {
    const modal = document.getElementById('modal-eliminar-detalle');
    if (!modal) return;
    modal.removeAttribute('hidden');
    modal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
}

function cerrarModalEliminarDetalle() {
    const modal = document.getElementById('modal-eliminar-detalle');
    if (!modal) return;
    modal.setAttribute('hidden', '');
    modal.classList.remove('is-open');
    document.body.style.overflow = '';
}

async function ejecutarEliminarDetalle() {
    const root = document.querySelector('[data-event-detail-view]');
    const btn = document.getElementById('btn-confirmar-eliminar-detalle');
    btn.disabled = true;
    btn.innerHTML = '<span aria-hidden="true">⏳</span> Eliminando…';

    try {
        const resp = await fetch(`${root.dataset.apiUrl}/${root.dataset.eventId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });

        if (!resp.ok && resp.status !== 204) {
            throw new Error('No se pudo eliminar el evento.');
        }

        cerrarModalEliminarDetalle();
        window.location.href = "{{ route('eventos.dashboard') }}";
    } catch (err) {
        alert(err.message || 'Error al eliminar el evento.');
        btn.disabled = false;
        btn.innerHTML = '<span aria-hidden="true">🗑</span> Sí, eliminar evento';
    }
}

/* ── Activación de pestaña si viene de hash (#tab-proveedores) ── */
document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#tab-proveedores' || window.location.hash === '#proveedores') {
        const btnTab = document.getElementById('tab-proveedores');
        if (btnTab) btnTab.click();
    }
});
</script>
@endsection
