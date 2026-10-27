@props(['evento' => null])

<article class="event-card" data-event-card data-event-id="{{ $evento?->id }}">
    <div class="event-card__header-row">
        <div class="event-card__labels">
            <span class="badge badge--type" data-event-type>{{ $evento?->tipo }}</span>
            <span class="badge badge--state" data-event-state>{{ $evento?->estado }}</span>
        </div>
        <button type="button" class="btn-card-delete" data-delete-event="{{ $evento?->id }}" data-event-name="{{ $evento?->nombre }}" title="Eliminar evento" aria-label="Eliminar evento">
            <span aria-hidden="true">🗑</span>
        </button>
    </div>
    <h2 class="event-card__title" data-event-name>{{ $evento?->nombre }}</h2>
    <div class="event-card__facts">
        <p><span aria-hidden="true">▣</span><span data-event-date>{{ $evento ? $evento->fecha_evento?->format('d/m/Y') : '' }}</span></p>
        <p><span aria-hidden="true">⌖</span><span data-event-location>{{ $evento?->ubicacion }}</span></p>
        <p><span aria-hidden="true">♧</span><span data-event-guests>{{ $evento?->numero_invitados ?? 0 }}</span> invitados</p>
        <p data-event-budget-row><span aria-hidden="true">$</span><span data-event-budget>{{ $evento ? '$' . number_format($evento->presupuesto, 0) : '' }}</span></p>
    </div>
    <footer class="event-card__footer">
        <span class="muted" data-event-folio>{{ $evento ? 'Folio ' . $evento->folio : '' }}</span>
        <a class="text-link" data-event-detail href="{{ $evento ? route('eventos.detalle', $evento) : '#' }}">Ver detalle <span aria-hidden="true">›</span></a>
    </footer>
</article>
