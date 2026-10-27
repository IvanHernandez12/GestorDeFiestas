/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa, Jorge Darien Cabrera Bastidas
 * @created 20/octubre/2026
 * @description Prepara el editor interactivo de mesas, sillas de prioridad y zonas del salón,
 *              con persistencia en base de datos corporativa.
 */

import { showToast } from './events.js';

/**
 * Prepara el editor de mesas, sillas de prioridad y zonas del salón con guardado persistente.
 *
 * @returns {void}
 */
export function initializeVenueEditor() {
    const canvas = document.querySelector('[data-venue-canvas]');
    const root = document.querySelector('[data-event-detail-view]');

    if (!canvas || !root) {
        return;
    }

    const eventoId = root.dataset.eventId;
    const apiUrl = root.dataset.apiUrl || '/api/eventos';
    let selectedPriority = 'Familia';
    let tableCounter = 0;

    // Conecta selector de prioridad del pincel
    document.querySelectorAll('[data-priority]').forEach((button) => {
        button.addEventListener('click', () => {
            selectedPriority = button.dataset.priority;
            document.querySelectorAll('[data-priority]').forEach((item) => item.classList.toggle('is-selected', item === button));
        });
    });

    // Botón agregar mesa
    document.querySelector('[data-add-table]')?.addEventListener('click', () => {
        tableCounter += 1;
        addTable(canvas, tableCounter, selectedPriority);
        actualizarEstadoModificado();
    });

    // Botón agregar zona (Pista, Escenario, Bar)
    document.querySelector('[data-add-zone]')?.addEventListener('click', () => {
        const typeSelect = document.querySelector('[data-zone-type]');
        const type = typeSelect ? typeSelect.value : 'Pista';
        addZone(canvas, type);
        actualizarEstadoModificado();
    });

    // Botón guardar lugares y croquis
    const saveBtn = document.querySelector('[data-save-venue]');
    saveBtn?.addEventListener('click', async () => {
        await guardarDistribucionSalon(canvas, root, saveBtn);
    });

    // Botón restablecer salón a valores por defecto
    const resetBtn = document.querySelector('[data-reset-venue]');
    resetBtn?.addEventListener('click', () => {
        if (confirm('¿Restablecer la distribución inicial con 4 mesas?')) {
            canvas.replaceChildren();
            tableCounter = 0;
            [1, 2, 3, 4].forEach((num) => {
                tableCounter = Math.max(tableCounter, num);
                addTable(canvas, num, 'Familia');
            });
            actualizarEstadoModificado('Distribución restablecida (sin guardar)');
        }
    });

    // Carga de distribución guardada o inicial
    cargarDistribucion(canvas, root, (highest) => {
        tableCounter = highest;
    });
}

/**
 * Actualiza la etiqueta de estado de los cambios en el salón.
 *
 * @param {string} [mensaje]
 */
function actualizarEstadoModificado(mensaje) {
    const statusLabel = document.querySelector('[data-venue-status]');
    if (statusLabel) {
        statusLabel.textContent = mensaje || '⚠️ Tienes cambios en el croquis sin guardar';
        statusLabel.style.color = '#B45309';
    }
}

/**
 * Carga el diseño guardado en base de datos o crea las mesas por defecto.
 *
 * @param {HTMLElement} canvas
 * @param {HTMLElement} root
 * @param {(max: number) => void} onLoaded
 */
function cargarDistribucion(canvas, root, onLoaded) {
    let layout = null;
    try {
        const raw = root.dataset.venueLayout;
        if (raw && raw !== 'null') {
            layout = typeof raw === 'string' ? JSON.parse(raw) : raw;
        }
    } catch {
        layout = null;
    }

    let highestNum = 0;

    if (layout && layout.tables && Array.isArray(layout.tables) && layout.tables.length > 0) {
        canvas.replaceChildren();
        layout.tables.forEach((t) => {
            const num = parseInt(t.number, 10) || 1;
            highestNum = Math.max(highestNum, num);
            addTable(canvas, num, 'General', t.x, t.y, t.chairs);
        });

        if (layout.zones && Array.isArray(layout.zones)) {
            layout.zones.forEach((z) => {
                addZone(canvas, z.type, z.x, z.y);
            });
        }

        const statusLabel = document.querySelector('[data-venue-status]');
        if (statusLabel) {
            statusLabel.textContent = '✓ Lugares guardados en el servidor';
            statusLabel.style.color = '#15803D';
        }
    } else {
        // Inicialización estándar por defecto con 4 mesas
        [1, 2, 3, 4].forEach((num) => {
            highestNum = Math.max(highestNum, num);
            addTable(canvas, num, 'Familia');
        });
    }

    onLoaded(highestNum);
}

/**
 * Serializa y envía la distribución del salón al servidor vía API REST.
 *
 * @param {HTMLElement} canvas
 * @param {HTMLElement} root
 * @param {HTMLButtonElement} button
 */
async function guardarDistribucionSalon(canvas, root, button) {
    const tables = [];
    canvas.querySelectorAll('.venue-table').forEach((table) => {
        const num = parseInt(table.getAttribute('data-table-number') || '1', 10);
        const x = parseInt(table.style.left, 10) || 0;
        const y = parseInt(table.style.top, 10) || 0;
        const chairs = [];
        table.querySelectorAll('.chair').forEach((chair) => {
            chairs.push(chair.dataset.priority || 'General');
        });
        tables.push({ number: num, x, y, chairs });
    });

    const zones = [];
    canvas.querySelectorAll('.venue-zone').forEach((zone) => {
        const type = zone.textContent.trim();
        const x = parseInt(zone.style.left, 10) || 0;
        const y = parseInt(zone.style.top, 10) || 0;
        zones.push({ type, x, y });
    });

    const payload = {
        distribucion_salon: {
            tables,
            zones,
            actualizado_en: new Date().toISOString(),
        },
    };

    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span aria-hidden="true">⏳</span> Guardando…';

    try {
        const response = await fetch(`${root.dataset.apiUrl}/${root.dataset.eventId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            throw new Error('No se pudo guardar la distribución.');
        }

        const data = await response.json();
        root.dataset.venueLayout = JSON.stringify(data.distribucion_salon);

        const statusLabel = document.querySelector('[data-venue-status]');
        if (statusLabel) {
            statusLabel.textContent = `✓ Guardado exitosamente (${new Date().toLocaleTimeString()})`;
            statusLabel.style.color = '#15803D';
        }

        showToast('¡Lugares y croquis del salón guardados exitosamente!');
    } catch (err) {
        showToast(err.message || 'Error al guardar distribución.');
    } finally {
        button.disabled = false;
        button.innerHTML = originalText;
    }
}

/**
 * Añade una mesa con ocho sillas que reciben la prioridad seleccionada.
 *
 * @param {HTMLElement} canvas
 * @param {number} tableNumber
 * @param {string} defaultPriority
 * @param {number} [customX]
 * @param {number} [customY]
 * @param {string[]} [chairPriorities]
 * @returns {void}
 */
function addTable(canvas, tableNumber, defaultPriority, customX, customY, chairPriorities) {
    const table = document.createElement('div');
    table.className = 'venue-table';
    table.setAttribute('aria-label', `Mesa ${tableNumber}`);
    table.setAttribute('data-table-number', String(tableNumber));

    if (customX !== undefined && customY !== undefined) {
        table.style.left = `${customX}px`;
        table.style.top = `${customY}px`;
    } else {
        const column = (tableNumber - 1) % 2;
        const row = Math.floor((tableNumber - 1) / 2);
        table.style.left = `${44 + column * 150}px`;
        table.style.top = `${60 + row * 150}px`;
    }

    const center = document.createElement('div');
    center.className = 'table-center';
    center.textContent = `M ${tableNumber}`;
    table.append(center);

    for (let index = 0; index < 8; index += 1) {
        const chair = document.createElement('button');
        chair.className = 'chair';
        chair.type = 'button';
        const chairPrio = chairPriorities && chairPriorities[index] ? chairPriorities[index] : defaultPriority;
        chair.dataset.priority = chairPrio;
        chair.setAttribute('aria-label', `Mesa ${tableNumber}, Silla ${index + 1}, prioridad ${chairPrio}`);
        chair.addEventListener('click', (event) => {
            event.stopPropagation();
            const selected = document.querySelector('.priority-option.is-selected')?.dataset.priority ?? 'General';
            chair.dataset.priority = selected;
            chair.setAttribute('aria-label', `Mesa ${tableNumber}, Silla ${index + 1}, prioridad ${selected}`);
            actualizarEstadoModificado();
        });
        table.append(chair);
    }

    const removeButton = document.createElement('button');
    removeButton.className = 'table-delete';
    removeButton.type = 'button';
    removeButton.setAttribute('aria-label', `Eliminar mesa ${tableNumber}`);
    removeButton.textContent = '×';
    removeButton.addEventListener('click', () => {
        table.remove();
        actualizarEstadoModificado();
    });
    table.append(removeButton);

    enableDrag(table, canvas);
    canvas.append(table);
}

/**
 * Añade una zona que puede moverse y redimensionarse.
 *
 * @param {HTMLElement} canvas
 * @param {string} type
 * @param {number} [customX]
 * @param {number} [customY]
 * @returns {void}
 */
function addZone(canvas, type, customX, customY) {
    const zone = document.createElement('div');
    zone.className = 'venue-zone';
    zone.textContent = type;
    zone.style.left = customX !== undefined ? `${customX}px` : '300px';
    zone.style.top = customY !== undefined ? `${customY}px` : '40px';

    const deleteZone = document.createElement('button');
    deleteZone.type = 'button';
    deleteZone.textContent = '×';
    deleteZone.style.cssText = 'position:absolute;top:2px;right:4px;border:none;background:none;color:#1E3A8A;cursor:pointer;font-weight:bold;';
    deleteZone.addEventListener('click', (e) => {
        e.stopPropagation();
        zone.remove();
        actualizarEstadoModificado();
    });
    zone.append(deleteZone);

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
        actualizarEstadoModificado();
    });
}
