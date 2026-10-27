/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa
 * @created 27/octubre/2026
 * @description Gestiona el croquis, sus mesas, sillas, zonas y persistencia.
 */

import { showToast } from './events.js';

const DEFAULT_TABLES = 4;
const DEFAULT_CHAIRS = 8;
const MIN_CHAIRS = 2;
const MAX_CHAIRS = 20;

/**
 * Inicializa el editor y recupera la distribución guardada.
 *
 * @returns {void}
 */
export function initializeVenueEditor() {
    const canvas = document.querySelector('[data-venue-canvas]');
    const root = document.querySelector('[data-event-detail-view]');

    if (!canvas || !root) {
        return;
    }

    let tableCounter = 0;
    const saveButton = document.querySelector('[data-save-venue]');
    const shapeSelect = document.querySelector('[data-table-shape]');
    const chairCountSelect = document.querySelector('[data-chair-count]');

    document.querySelectorAll('[data-priority]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-priority]').forEach((item) => {
                item.classList.toggle('is-selected', item === button);
            });
        });
    });

    document.querySelector('[data-add-table]')?.addEventListener('click', () => {
        tableCounter += 1;
        const type = shapeSelect?.value ?? 'round';
        const chairCount = Number(chairCountSelect?.value) || DEFAULT_CHAIRS;
        addTable(canvas, tableCounter, type, chairCount);
        updateDirtyState();
    });

    document.querySelector('[data-add-zone]')?.addEventListener('click', () => {
        const zoneType = document.querySelector('[data-zone-type]')?.value ?? 'Pista';
        addZone(canvas, { type: zoneType });
        updateDirtyState();
    });

    saveButton?.addEventListener('click', () => {
        saveVenueLayout(canvas, root, saveButton);
    });

    document.querySelector('[data-reset-venue]')?.addEventListener('click', async () => {
        if (!window.confirm('¿Restablecer el salón a cuatro mesas redondas, ocho sillas por mesa y un escenario?')) {
            return;
        }

        renderDefaultLayout(canvas);
        tableCounter = DEFAULT_TABLES;
        updateDirtyState('Guardando distribución predeterminada…');

        if (saveButton) {
            await saveVenueLayout(canvas, root, saveButton);
        }
    });

    tableCounter = loadVenueLayout(canvas, root);
}

/**
 * Muestra que el croquis tiene cambios sin guardar.
 *
 * @param {string} [message]
 * @returns {void}
 */
function updateDirtyState(message = 'Hay cambios en el croquis sin guardar.') {
    const statusLabel = document.querySelector('[data-venue-status]');

    if (statusLabel) {
        statusLabel.textContent = message;
        statusLabel.style.color = '#B45309';
    }
}

/**
 * Crea la distribución inicial estándar.
 *
 * @param {HTMLElement} canvas
 * @returns {void}
 */
function renderDefaultLayout(canvas) {
    canvas.replaceChildren();

    for (let tableNumber = 1; tableNumber <= DEFAULT_TABLES; tableNumber += 1) {
        addTable(canvas, tableNumber, 'round', DEFAULT_CHAIRS, 'General');
    }

    addZone(canvas, {
        type: 'Escenario',
        x: 340,
        y: 40,
        width: 170,
        height: 80,
    });
}

/**
 * Recupera la distribución guardada o presenta el diseño inicial.
 *
 * @param {HTMLElement} canvas
 * @param {HTMLElement} root
 * @returns {number}
 */
function loadVenueLayout(canvas, root) {
    let layout = null;

    try {
        layout = root.dataset.venueLayout && root.dataset.venueLayout !== 'null'
            ? JSON.parse(root.dataset.venueLayout)
            : null;
    } catch {
        layout = null;
    }

    if (!layout || !Array.isArray(layout.tables)) {
        renderDefaultLayout(canvas);
        return DEFAULT_TABLES;
    }

    canvas.replaceChildren();
    let highestTableNumber = 0;

    layout.tables.forEach((tableData) => {
        const tableNumber = Number.parseInt(tableData.number, 10) || 1;
        const chairPriorities = Array.isArray(tableData.chairs) ? tableData.chairs : [];
        const chairCount = Number(tableData.chairCount) || chairPriorities.length || DEFAULT_CHAIRS;
        const type = tableData.type === 'rectangular' ? 'rectangular' : 'round';

        highestTableNumber = Math.max(highestTableNumber, tableNumber);
        addTable(canvas, tableNumber, type, chairCount, 'General', tableData.x, tableData.y, chairPriorities);
    });

    if (Array.isArray(layout.zones)) {
        layout.zones.forEach((zoneData) => addZone(canvas, zoneData));
    }

    const statusLabel = document.querySelector('[data-venue-status]');
    if (statusLabel) {
        statusLabel.textContent = 'Distribución guardada';
        statusLabel.style.color = '#15803D';
    }

    return highestTableNumber;
}

/**
 * Guarda en Laravel las mesas, prioridades y espacios del croquis.
 *
 * @param {HTMLElement} canvas
 * @param {HTMLElement} root
 * @param {HTMLButtonElement} button
 * @returns {Promise<void>}
 */
async function saveVenueLayout(canvas, root, button) {
    const tables = Array.from(canvas.querySelectorAll('.venue-table')).map((table) => ({
        number: Number(table.dataset.tableNumber),
        type: table.dataset.tableType,
        chairCount: Number(table.dataset.chairCount),
        x: Math.round(Number.parseFloat(table.style.left) || 0),
        y: Math.round(Number.parseFloat(table.style.top) || 0),
        chairs: Array.from(table.querySelectorAll('.chair')).map((chair) => chair.dataset.priority || 'General'),
    }));

    const zones = Array.from(canvas.querySelectorAll('.venue-zone')).map((zone) => ({
        type: zone.dataset.zoneType,
        x: Math.round(Number.parseFloat(zone.style.left) || 0),
        y: Math.round(Number.parseFloat(zone.style.top) || 0),
        width: zone.offsetWidth,
        height: zone.offsetHeight,
    }));

    const originalText = button.innerHTML;
    button.disabled = true;
    button.textContent = 'Guardando…';

    try {
        const response = await fetch(`${root.dataset.apiUrl}/${root.dataset.eventId}`, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                distribucion_salon: {
                    tables,
                    zones,
                    actualizado_en: new Date().toISOString(),
                },
            }),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationError = Object.values(data.errors ?? {}).flat()[0];
            throw new Error(validationError ?? data.message ?? 'No se pudo guardar la distribución.');
        }

        root.dataset.venueLayout = JSON.stringify(data.distribucion_salon);
        const statusLabel = document.querySelector('[data-venue-status]');
        if (statusLabel) {
            statusLabel.textContent = `Guardado ${new Date().toLocaleTimeString('es-MX')}`;
            statusLabel.style.color = '#15803D';
        }
        showToast('La distribución del salón quedó guardada.');
    } catch (error) {
        showToast(error.message || 'Error al guardar la distribución.');
        updateDirtyState();
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

/**
 * Agrega una mesa con forma y capacidad configurables.
 *
 * @param {HTMLElement} canvas
 * @param {number} tableNumber
 * @param {string} type
 * @param {number} chairCount
 * @param {string} [defaultPriority]
 * @param {number} [customX]
 * @param {number} [customY]
 * @param {string[]} [chairPriorities]
 * @returns {void}
 */
function addTable(canvas, tableNumber, type, chairCount, defaultPriority = 'General', customX, customY, chairPriorities = []) {
    const tableType = type === 'rectangular' ? 'rectangular' : 'round';
    const table = document.createElement('div');
    table.className = `venue-table venue-table--${tableType}`;
    table.dataset.tableNumber = String(tableNumber);
    table.dataset.tableType = tableType;
    table.setAttribute('aria-label', `Mesa ${tableNumber} ${tableType === 'round' ? 'redonda' : 'rectangular'}`);

    const column = (tableNumber - 1) % 2;
    const row = Math.floor((tableNumber - 1) / 2);
    table.style.left = `${customX ?? 44 + column * 150}px`;
    table.style.top = `${customY ?? 60 + row * 150}px`;

    const center = document.createElement('div');
    center.className = 'table-center';
    center.textContent = `M ${tableNumber}`;
    table.append(center);

    const controls = document.createElement('div');
    controls.className = 'table-chair-controls';
    const decreaseButton = createChairButton('−', `Quitar silla a la mesa ${tableNumber}`);
    const countLabel = document.createElement('span');
    countLabel.className = 'chair-count-label';
    const increaseButton = createChairButton('+', `Agregar silla a la mesa ${tableNumber}`);
    controls.append(decreaseButton, countLabel, increaseButton);
    table.append(controls);

    const updateChairCount = (count) => {
        const priorities = Array.from(table.querySelectorAll('.chair')).map((chair) => chair.dataset.priority);
        const boundedCount = Math.max(MIN_CHAIRS, Math.min(MAX_CHAIRS, count));
        table.dataset.chairCount = String(boundedCount);
        countLabel.textContent = String(boundedCount);
        renderChairs(table, tableType, boundedCount, priorities, defaultPriority);
        decreaseButton.disabled = boundedCount <= MIN_CHAIRS;
        increaseButton.disabled = boundedCount >= MAX_CHAIRS;
        updateDirtyState();
    };

    decreaseButton.addEventListener('click', () => updateChairCount(Number(table.dataset.chairCount) - 1));
    increaseButton.addEventListener('click', () => updateChairCount(Number(table.dataset.chairCount) + 1));
    updateChairCount(chairCount);

    const deleteButton = createChairButton('×', `Eliminar mesa ${tableNumber}`);
    deleteButton.className = 'table-delete';
    deleteButton.addEventListener('click', () => {
        table.remove();
        updateDirtyState();
    });
    table.append(deleteButton);

    enableDrag(table, canvas);
    canvas.append(table);
}

/**
 * Crea un botón accesible para los controles de las mesas.
 *
 * @param {string} label
 * @param {string} accessibleName
 * @returns {HTMLButtonElement}
 */
function createChairButton(label, accessibleName) {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = label;
    button.setAttribute('aria-label', accessibleName);

    return button;
}

/**
 * Dibuja sillas circulares alrededor de la mesa.
 *
 * @param {HTMLElement} table
 * @param {string} type
 * @param {number} count
 * @param {string[]} priorities
 * @param {string} defaultPriority
 * @returns {void}
 */
function renderChairs(table, type, count, priorities, defaultPriority) {
    table.querySelectorAll('.chair').forEach((chair) => chair.remove());

    for (let index = 0; index < count; index += 1) {
        const chair = document.createElement('button');
        chair.className = 'chair';
        chair.type = 'button';
        chair.dataset.priority = priorities[index] || defaultPriority;
        chair.setAttribute('aria-label', `Silla ${index + 1}, prioridad ${chair.dataset.priority}`);
        positionChair(chair, type, index, count);
        chair.addEventListener('click', (event) => {
            event.stopPropagation();
            chair.dataset.priority = document.querySelector('.priority-option.is-selected')?.dataset.priority ?? 'General';
            chair.setAttribute('aria-label', `Silla ${index + 1}, prioridad ${chair.dataset.priority}`);
            updateDirtyState();
        });
        table.append(chair);
    }
}

/**
 * Coloca una silla alrededor del perímetro de una mesa.
 *
 * @param {HTMLButtonElement} chair
 * @param {string} type
 * @param {number} index
 * @param {number} count
 * @returns {void}
 */
function positionChair(chair, type, index, count) {
    if (type === 'round') {
        const angle = -Math.PI / 2 + (2 * Math.PI * index) / count;
        chair.style.left = `${Math.round(70 + 54 * Math.cos(angle) - 8)}px`;
        chair.style.top = `${Math.round(75 + 54 * Math.sin(angle) - 8)}px`;
        return;
    }

    const width = 106;
    const height = 68;
    const perimeter = 2 * (width + height);
    const distance = (perimeter * index) / count;
    let x;
    let y;

    if (distance <= width) {
        x = 17 + distance;
        y = 41;
    } else if (distance <= width + height) {
        x = 17 + width;
        y = 41 + distance - width;
    } else if (distance <= (2 * width) + height) {
        x = 17 + width - (distance - width - height);
        y = 41 + height;
    } else {
        x = 17;
        y = 41 + height - (distance - (2 * width) - height);
    }

    chair.style.left = `${Math.round(x - 8)}px`;
    chair.style.top = `${Math.round(y - 8)}px`;
}

/**
 * Agrega una zona identificable y de tamaño modificable.
 *
 * @param {HTMLElement} canvas
 * @param {Record<string, any>} zoneData
 * @returns {void}
 */
function addZone(canvas, zoneData) {
    const zone = document.createElement('div');
    zone.className = 'venue-zone';
    zone.dataset.zoneType = zoneData.type || 'Zona';
    zone.style.left = `${Number.isFinite(Number(zoneData.x)) ? Number(zoneData.x) : 300}px`;
    zone.style.top = `${Number.isFinite(Number(zoneData.y)) ? Number(zoneData.y) : 40}px`;
    zone.style.width = `${Number.isFinite(Number(zoneData.width)) ? Number(zoneData.width) : 160}px`;
    zone.style.height = `${Number.isFinite(Number(zoneData.height)) ? Number(zoneData.height) : 80}px`;

    const label = document.createElement('span');
    label.className = 'venue-zone__label';
    label.textContent = zone.dataset.zoneType;

    const deleteButton = createChairButton('×', `Eliminar zona ${zone.dataset.zoneType}`);
    deleteButton.className = 'zone-delete';
    deleteButton.addEventListener('click', (event) => {
        event.stopPropagation();
        zone.remove();
        updateDirtyState();
    });

    zone.append(label, deleteButton);
    enableDrag(zone, canvas);
    zone.addEventListener('pointerup', () => updateDirtyState());
    canvas.append(zone);
}

/**
 * Permite arrastrar mesas y zonas dentro del área del salón.
 *
 * @param {HTMLElement} element
 * @param {HTMLElement} canvas
 * @returns {void}
 */
function enableDrag(element, canvas) {
    let pointerOffsetX = 0;
    let pointerOffsetY = 0;

    element.addEventListener('pointerdown', (event) => {
        if (event.target.closest('button')) {
            return;
        }

        const bounds = element.getBoundingClientRect();
        const resizingZone = element.classList.contains('venue-zone')
            && event.clientX >= bounds.right - 20
            && event.clientY >= bounds.bottom - 20;

        if (resizingZone) {
            return;
        }

        pointerOffsetX = event.clientX - bounds.left;
        pointerOffsetY = event.clientY - bounds.top;
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
        updateDirtyState();
    });
}
