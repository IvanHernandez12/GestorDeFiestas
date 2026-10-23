/**
 * Prepara el editor de mesas, sillas de prioridad y zonas del salón.
 *
 * @returns {void}
 */
export function initializeVenueEditor() {
    const canvas = document.querySelector('[data-venue-canvas]');

    if (!canvas) {
        return;
    }

    let selectedPriority = 'Familia';
    let tableCount = 0;

    document.querySelectorAll('[data-priority]').forEach((button) => {
        button.addEventListener('click', () => {
            selectedPriority = button.dataset.priority;
            document.querySelectorAll('[data-priority]').forEach((item) => item.classList.toggle('is-selected', item === button));
        });
    });

    document.querySelector('[data-add-table]')?.addEventListener('click', () => {
        tableCount += 1;
        addTable(canvas, tableCount, selectedPriority);
    });

    document.querySelector('[data-add-zone]')?.addEventListener('click', () => {
        const type = document.querySelector('[data-zone-type]').value;
        addZone(canvas, type);
    });

    [1, 2, 3, 4].forEach((tableNumber) => addTable(canvas, tableNumber, selectedPriority));
}

/**
 * Añade una mesa con ocho sillas que reciben la prioridad seleccionada.
 *
 * @param {HTMLElement} canvas
 * @param {number} tableNumber
 * @param {string} priority
 * @returns {void}
 */
function addTable(canvas, tableNumber, priority) {
    const table = document.createElement('div');
    table.className = 'venue-table';
    table.setAttribute('aria-label', `Mesa ${tableNumber}`);
    const column = (tableNumber - 1) % 2;
    const row = Math.floor((tableNumber - 1) / 2);
    table.style.left = `${44 + column * 150}px`;
    table.style.top = `${60 + row * 150}px`;

    const center = document.createElement('div');
    center.className = 'table-center';
    center.textContent = 'MESA';
    table.append(center);

    for (let index = 0; index < 8; index += 1) {
        const chair = document.createElement('button');
        chair.className = 'chair';
        chair.type = 'button';
        chair.dataset.priority = priority;
        chair.setAttribute('aria-label', `Silla ${index + 1}, prioridad ${priority}`);
        chair.addEventListener('click', (event) => {
            event.stopPropagation();
            chair.dataset.priority = document.querySelector('.priority-option.is-selected')?.dataset.priority ?? 'General';
            chair.setAttribute('aria-label', `Silla ${index + 1}, prioridad ${chair.dataset.priority}`);
        });
        table.append(chair);
    }

    const removeButton = document.createElement('button');
    removeButton.className = 'table-delete';
    removeButton.type = 'button';
    removeButton.setAttribute('aria-label', `Eliminar mesa ${tableNumber}`);
    removeButton.textContent = '×';
    removeButton.addEventListener('click', () => table.remove());
    table.append(removeButton);
    enableDrag(table, canvas);
    canvas.append(table);
}

/**
 * Añade una zona que puede moverse y redimensionarse.
 *
 * @param {HTMLElement} canvas
 * @param {string} type
 * @returns {void}
 */
function addZone(canvas, type) {
    const zone = document.createElement('div');
    zone.className = 'venue-zone';
    zone.textContent = type;
    zone.style.left = '300px';
    zone.style.top = '40px';
    enableDrag(zone, canvas);
    canvas.append(zone);
}

/**
 * Habilita el arrastre de una mesa o zona dentro del croquis.
 *
 * @param {HTMLElement} element
 * @param {HTMLElement} canvas
 * @returns {void}
 */
function enableDrag(element, canvas) {
    let pointerOffsetX = 0;
    let pointerOffsetY = 0;

    element.addEventListener('pointerdown', (event) => {
        if (event.target.closest('button') || event.target.closest('.chair')) {
            return;
        }

        const elementBounds = element.getBoundingClientRect();
        pointerOffsetX = event.clientX - elementBounds.left;
        pointerOffsetY = event.clientY - elementBounds.top;
        element.setPointerCapture(event.pointerId);
    });

    element.addEventListener('pointermove', (event) => {
        if (!element.hasPointerCapture(event.pointerId)) {
            return;
        }

        const canvasBounds = canvas.getBoundingClientRect();
        const x = Math.max(0, Math.min(event.clientX - canvasBounds.left - pointerOffsetX, canvas.clientWidth - element.offsetWidth));
        const y = Math.max(0, Math.min(event.clientY - canvasBounds.top - pointerOffsetY, canvas.clientHeight - element.offsetHeight));
        element.style.left = `${x}px`;
        element.style.top = `${y}px`;
    });
}
