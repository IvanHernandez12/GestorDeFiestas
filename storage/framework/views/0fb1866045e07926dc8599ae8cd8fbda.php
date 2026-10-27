<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['evento' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['evento' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<article class="event-card" data-event-card data-event-id="<?php echo e($evento?->id); ?>">
    <div class="event-card__header-row">
        <div class="event-card__labels">
            <span class="badge badge--type" data-event-type><?php echo e($evento?->tipo); ?></span>
            <span class="badge badge--state" data-event-state><?php echo e($evento?->estado); ?></span>
        </div>
        <button type="button" class="btn-card-delete" data-delete-event="<?php echo e($evento?->id); ?>" data-event-name="<?php echo e($evento?->nombre); ?>" title="Eliminar evento" aria-label="Eliminar evento">
            <span aria-hidden="true">🗑</span>
        </button>
    </div>
    <h2 class="event-card__title" data-event-name><?php echo e($evento?->nombre); ?></h2>
    <div class="event-card__facts">
        <p><span aria-hidden="true">▣</span><span data-event-date><?php echo e($evento ? $evento->fecha_evento?->format('d/m/Y') : ''); ?></span></p>
        <p><span aria-hidden="true">⌖</span><span data-event-location><?php echo e($evento?->ubicacion); ?></span></p>
        <p><span aria-hidden="true">♧</span><span data-event-guests><?php echo e($evento?->numero_invitados ?? 0); ?></span> invitados</p>
        <p data-event-budget-row><span aria-hidden="true">$</span><span data-event-budget><?php echo e($evento ? '$' . number_format($evento->presupuesto, 0) : ''); ?></span></p>
    </div>
    <footer class="event-card__footer">
        <span class="muted" data-event-folio><?php echo e($evento ? 'Folio ' . $evento->folio : ''); ?></span>
        <a class="text-link" data-event-detail href="<?php echo e($evento ? route('eventos.detalle', $evento) : '#'); ?>">Ver detalle <span aria-hidden="true">›</span></a>
    </footer>
</article>
<?php /**PATH C:\xammp\htdocs\laravel\proyecto\resources\views/components/event-card.blade.php ENDPATH**/ ?>