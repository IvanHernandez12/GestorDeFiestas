import { initializeEventModal, loadEventDashboard, loadEventDetail, showToast } from './events.js';
import { initializeVenueEditor } from './venue-editor.js';

/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa
 * @created 20/octubre/2026
 * @description Inicializa los componentes de la interfaz de eventos y modales.
 */

document.addEventListener('DOMContentLoaded', () => {
    const eventList = document.querySelector('[data-event-list]');
    const eventDetail = document.querySelector('[data-event-detail-view]');

    if (eventList) {
        loadEventDashboard(eventList);
        initializeEventModal(eventList);
    }

    if (eventDetail) {
        loadEventDetail(eventDetail);
        initializeEventTabs();
        initializeVenueEditor();
        initializePhotoPicker();
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('.toast').forEach((toast) => toast.remove());
    });

    window.addEventListener('error', (event) => {
        if (event.message) {
            console.error(event.message);
        }
    });
});

/**
 * Activa los paneles de la navegación por pestañas.
 *
 * @returns {void}
 */
function initializeEventTabs() {
    const tabs = Array.from(document.querySelectorAll('[data-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-panel]'));

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const selectedTab = tab.dataset.tab;

            tabs.forEach((item) => {
                const isActive = item === tab;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-selected', String(isActive));
                item.tabIndex = isActive ? 0 : -1;
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.panel !== selectedTab;
            });
        });
    });
}

/**
 * Muestra previsualizaciones locales de las fotos seleccionadas.
 *
 * @returns {void}
 */
function initializePhotoPicker() {
    const picker = document.querySelector('[data-photo-input]');
    const grid = document.querySelector('[data-photo-grid]');

    picker?.addEventListener('change', () => {
        if (!picker.files?.length || !grid) {
            return;
        }

        grid.querySelector('.photo-empty')?.remove();

        Array.from(picker.files).forEach((file) => {
            if (!file.type.startsWith('image/')) {
                showToast('Selecciona un archivo de imagen válido.');
                return;
            }

            const image = document.createElement('img');
            image.className = 'photo-thumb';
            image.alt = file.name;
            image.src = URL.createObjectURL(file);
            grid.append(image);
        });

        picker.value = '';
    });
}
