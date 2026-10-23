@props(['icon', 'label', 'value', 'id'])
<article class="summary-card">
    <span class="summary-card__icon" aria-hidden="true">{{ $icon }}</span>
    <span class="summary-card__label">{{ $label }}</span>
    <strong class="summary-card__value" id="{{ $id }}">{{ $value }}</strong>
</article>
