/**
 * Solicita JSON a los endpoints de eventos.
 *
 * @param {string} url
 * @param {RequestInit} options
 * @returns {Promise<any>}
 */


/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa
 * @created 20/octubre/2026
 * @description 
 */


async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...options.headers,
        },
        ...options,
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        const validationError = Object.values(errorData.errors ?? {}).flat()[0];
        throw new Error(validationError ?? errorData.message ?? 'No se pudo completar la solicitud.');
    }

    return response.status === 204 ? null : response.json();
}

/**
 * Presenta una notificación cerrable.
 *
 * @param {string} message
 * @returns {void}
 */
export function showToast(message) {
    const region = document.querySelector('.toast-region');

    if (!region) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'status');
    toast.append(document.createTextNode(message));

    const closeButton = document.createElement('button');
    closeButton.className = 'toast__close';
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Cerrar notificación');
    closeButton.textContent = '×';
    toast.append(closeButton);
    region.append(toast);
}

/**
 * Carga y presenta las tarjetas del dashboard.
 *
 * @param {HTMLElement} root
 * @returns {Promise<void>}
 */
export async function loadEventDashboard(root) {
    const container = root.querySelector('[data-events-container]');
    const countLabel = root.querySelector('[data-event-count]');
    const activeEventsStat = document.querySelector('#stat-active-events');
    const totalGuestsStat = document.querySelector('#stat-total-guests');

    try {
        const response = await requestJson(root.dataset.apiUrl);
        const eventos = response.data ?? response;
        if (countLabel) {
            countLabel.textContent = `${eventos.length} ${eventos.length === 1 ? 'evento' : 'eventos'}`;
        }
        if (activeEventsStat) {
            activeEventsStat.textContent = `${eventos.length} eventos`;
        }
        if (totalGuestsStat) {
            const totalGuests = eventos.reduce((sum, e) => sum + (parseInt(e.numero_invitados, 10) || 0), 0);
            totalGuestsStat.textContent = `${totalGuests} comensales`;
        }

        container.replaceChildren();

        if (!eventos.length) {
            container.innerHTML = '<p class="loading-state">Aún no hay eventos registrados.</p>';
            return;
        }

        eventos.forEach((evento) => container.append(createEventCard(evento)));
    } catch (error) {
        // Si hay tarjetas pre-renderizadas por el servidor, consérvalas
        if (container.querySelectorAll('.event-card').length === 0) {
            container.innerHTML = '<p class="error-state">No se pudieron cargar los eventos del servidor.</p>';
        }
    }
}

/**
 * Crea la tarjeta segura de un evento.
 *
 * @param {Record<string, any>} evento
 * @returns {HTMLElement}
 */
function createEventCard(evento) {
    const template = document.querySelector('#event-card-template');
    const card = template.content.firstElementChild.cloneNode(true);
    card.querySelector('[data-event-type]').textContent = evento.tipo;
    card.querySelector('[data-event-state]').textContent = evento.estado;
    card.querySelector('[data-event-name]').textContent = evento.nombre;
    card.querySelector('[data-event-date]').textContent = formatLongDate(evento.fecha_evento);
    card.querySelector('[data-event-location]').textContent = evento.ubicacion;
    card.querySelector('[data-event-guests]').textContent = evento.numero_invitados ?? 0;
    
    const budgetElem = card.querySelector('[data-event-budget]');
    if (budgetElem) {
        budgetElem.textContent = evento.presupuesto ? formatCurrency(evento.presupuesto) : '$0';
    }

    card.querySelector('[data-event-folio]').textContent = `Folio ${evento.folio}`;
    card.querySelector('[data-event-detail]').href = `/eventos/${evento.id}`;

    return card;
}

/**
 * Prepara el modal interactivo de creación de eventos y su envío REST.
 *
 * @param {HTMLElement} root
 * @returns {void}
 */
export function initializeEventModal(root) {
    const modal = document.querySelector('[data-event-modal]');
    const form = modal?.querySelector('[data-event-create-form]');
    const folioInput = modal?.querySelector('#input-modal-folio');
    const folioDisplay = modal?.querySelector('#display-modal-folio');
    const regenButton = modal?.querySelector('#btn-regenerar-folio');
    const errorBox = modal?.querySelector('[data-event-form-error]');

    if (!modal || !form) {
        return;
    }

    function generateFolio() {
        const randomNum = Math.floor(100000 + Math.random() * 900000);
        const folio = `EVT-${randomNum}`;
        if (folioInput) {
            folioInput.value = folio;
        }
        if (folioDisplay) {
            folioDisplay.textContent = folio;
        }
    }

    function openModal() {
        generateFolio();
        if (errorBox) {
            errorBox.setAttribute('hidden', '');
            errorBox.textContent = '';
        }
        modal.removeAttribute('hidden');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        const nameField = form.querySelector('input[name="nombre"]');
        setTimeout(() => nameField?.focus(), 50);
    }

    function closeModal() {
        modal.setAttribute('hidden', '');
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        form.reset();
    }

    document.querySelectorAll('[data-open-event-modal]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            openModal();
        });
    });

    document.querySelectorAll('[data-close-event-modal]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal();
        });
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    regenButton?.addEventListener('click', (event) => {
        event.preventDefault();
        generateFolio();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (errorBox) {
            errorBox.hidden = true;
            errorBox.textContent = '';
        }

        const formData = new FormData(form);
        const datos = Object.fromEntries(formData.entries());

        if (datos.numero_invitados) {
            datos.numero_invitados = parseInt(datos.numero_invitados, 10);
        }
        if (datos.presupuesto) {
            datos.presupuesto = parseFloat(datos.presupuesto);
        }

        const apiUrl = root.dataset.apiUrl || '/api/eventos';
        const submitBtn = form.querySelector('#btn-confirmar-creacion');

        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span aria-hidden="true">⏳</span> Registrando…';
            }

            await requestJson(apiUrl, {
                method: 'POST',
                body: JSON.stringify(datos),
            });

            closeModal();
            showToast(`¡Evento "${datos.nombre}" iniciado exitosamente!`);
            await loadEventDashboard(root);
        } catch (error) {
            if (errorBox) {
                errorBox.textContent = error.message;
                errorBox.hidden = false;
            } else {
                showToast(error.message);
            }
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span aria-hidden="true">＋</span> Guardar y comenzar evento';
            }
        }
    });
}

/**
 * Carga el evento y conecta el formulario REST de invitados.
 *
 * @param {HTMLElement} root
 * @returns {Promise<void>}
 */
export async function loadEventDetail(root) {
    try {
        const evento = await requestJson(`${root.dataset.apiUrl}/${root.dataset.eventId}`);
        renderEvent(evento);
        await loadGuests(root, evento.id);
        initializeGuestForm(root, evento.id);
    } catch (error) {
        document.querySelector('#detail-name').textContent = 'No se pudo cargar el evento';
        showToast(error.message);
    }
}

/**
 * Rellena las tarjetas de resumen y la pestaña de información.
 *
 * @param {Record<string, any>} evento
 * @returns {void}
 */
function renderEvent(evento) {
    document.querySelector('#detail-type').textContent = evento.tipo;
    document.querySelector('#detail-state').textContent = evento.estado;
    document.querySelector('#detail-name').textContent = evento.nombre;
    document.querySelector('#detail-folio').textContent = evento.folio;
    document.querySelector('#summary-date').textContent = formatShortDate(evento.fecha_evento);
    document.querySelector('#summary-location').textContent = evento.ubicacion;
    document.querySelector('#summary-guests').textContent = evento.numero_invitados;
    document.querySelector('#summary-budget').textContent = formatCurrency(evento.presupuesto);
    document.querySelector('#event-rules').textContent = evento.reglamento || 'Sin reglamento registrado';
    document.querySelector('#event-priorities').textContent = Array.isArray(evento.prioridad_invitados)
        ? `Por categorías (${evento.prioridad_invitados.join(', ')})`
        : 'Por categorías (Familia, Amigos, Trabajo, General)';
    document.querySelector('#qr-folio').textContent = `Folio ${evento.folio}`;
}

/**
 * Carga los invitados de un evento y muestra su estado vacío.
 *
 * @param {HTMLElement} root
 * @param {number} eventoId
 * @returns {Promise<void>}
 */
async function loadGuests(root, eventoId) {
    const list = root.querySelector('[data-guest-list]');
    const invitados = await requestJson(`${root.dataset.apiUrl}/${eventoId}/invitados`);
    list.replaceChildren();

    if (!invitados.length) {
        const emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = '<span class="empty-state__icon" aria-hidden="true">✉</span><p>Aún no hay invitados</p>';
        list.append(emptyState);
        return;
    }

    invitados.forEach((invitado) => list.append(createGuestRow(invitado)));
}

/**
 * Construye una fila de invitado.
 *
 * @param {Record<string, any>} invitado
 * @returns {HTMLElement}
 */
function createGuestRow(invitado) {
    const row = document.createElement('article');
    row.className = 'guest-row';
    const identity = document.createElement('div');
    const name = document.createElement('strong');
    name.textContent = invitado.nombre;
    const email = document.createElement('p');
    email.textContent = invitado.correo;
    identity.append(name, email);
    const category = document.createElement('span');
    category.className = 'guest-row__category';
    category.textContent = invitado.categoria;
    row.append(identity, category);

    return row;
}

/**
 * Envía el formulario de invitado al API y actualiza la lista.
 *
 * @param {HTMLElement} root
 * @param {number} eventoId
 * @returns {void}
 */
function initializeGuestForm(root, eventoId) {
    const form = root.querySelector('[data-guest-form]');
    const errorMessage = form.querySelector('[data-form-error]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorMessage.hidden = true;
        const formData = new FormData(form);
        const datos = Object.fromEntries(formData.entries());

        try {
            await requestJson(`${root.dataset.apiUrl}/${eventoId}/invitados`, {
                method: 'POST',
                body: JSON.stringify(datos),
            });
            form.reset();
            await loadGuests(root, eventoId);
            showToast('Invitado agregado correctamente.');
        } catch (error) {
            errorMessage.textContent = error.message;
            errorMessage.hidden = false;
        }
    });
}

/**
 * Da formato de moneda mexicana sin centavos para cantidades enteras.
 *
 * @param {string|number} value
 * @returns {string}
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

/**
 * Da formato largo a una fecha de evento.
 *
 * @param {string} value
 * @returns {string}
 */
function formatLongDate(value) {
    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${String(value).slice(0, 10)}T12:00:00Z`));
}

/**
 * Da formato compacto a una fecha de resumen.
 *
 * @param {string} value
 * @returns {string}
 */
function formatShortDate(value) {
    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${String(value).slice(0, 10)}T12:00:00Z`));
}
