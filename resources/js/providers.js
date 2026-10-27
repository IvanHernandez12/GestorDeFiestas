/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa
 * @created 27/octubre/2026
 * @description Permite seleccionar propuestas de proveedores ajustadas al evento.
 */

const CATALOG = [
    { key: 'catering', name: 'Banquetes Imperial Gourmet', category: 'Catering', unitPrice: 250, perGuest: true, types: ['boda', 'xv anos', 'cumpleanos', 'graduacion', 'bautizo', 'corporativo', 'reunion social'], phone: '+52 55 4500 1020', email: 'eventos@imperialgourmet.mx' },
    { key: 'mobiliario', name: 'Lounge & Decor Elegance', category: 'Mobiliario', price: 12000, types: ['boda', 'xv anos', 'cumpleanos', 'graduacion', 'bautizo', 'corporativo', 'reunion social'], phone: '+52 55 4500 1021', email: 'reservas@elegance.mx' },
    { key: 'audio', name: 'DJ Nova & Robotic Stage', category: 'Audio e iluminación', price: 10000, types: ['boda', 'xv anos', 'cumpleanos', 'graduacion', 'corporativo', 'reunion social'], phone: '+52 55 4500 1022', email: 'agenda@djnova.mx' },
    { key: 'reposteria', name: 'Sweet Dreams Pastelería', category: 'Repostería', price: 4000, types: ['boda', 'xv anos', 'cumpleanos', 'bautizo', 'reunion social'], phone: '+52 55 4500 1023', email: 'pedidos@sweetdreams.mx' },
    { key: 'fotografia', name: 'Lumiere Foto & Cinema', category: 'Fotografía', price: 12000, types: ['boda', 'xv anos', 'cumpleanos', 'graduacion', 'bautizo', 'corporativo', 'reunion social'], phone: '+52 55 4500 1024', email: 'hola@lumiere.mx' },
    { key: 'transporte', name: 'Transporte Elite', category: 'Transporte', price: 6000, types: ['boda', 'xv anos', 'graduacion', 'corporativo'], phone: '+52 55 4500 1025', email: 'servicio@transporteelite.mx' },
];

/** @param {HTMLElement} root @returns {Promise<void>} */
export async function initializeProviderSelection(root) {
    const grid = root.querySelector('[data-provider-options]');
    const feedback = root.querySelector('[data-provider-feedback]');
    const eventId = root.dataset.eventId;
    try {
        const response = await fetch(`${root.dataset.apiUrl}/${eventId}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('No se pudieron cargar los datos del evento.');
        const evento = await response.json();
        const totalLimit = Number(evento.presupuesto) * 0.7;
        const individualLimit = Number(evento.presupuesto) * 0.35;
        const tipo = normalize(evento.tipo);
        const opciones = CATALOG.map((provider) => ({
            ...provider,
            cost: provider.perGuest ? provider.unitPrice * Number(evento.numero_invitados) : provider.price,
        })).filter((provider) => provider.types.includes(tipo) && provider.cost <= individualLimit);

        root.querySelector('[data-event-name]').textContent = evento.nombre;
        root.querySelector('[data-event-budget]').textContent = money(evento.presupuesto);
        root.querySelector('[data-event-type]').textContent = evento.tipo;
        root.querySelector('[data-event-date]').textContent = new Intl.DateTimeFormat('es-MX', { dateStyle: 'long', timeZone: 'UTC' }).format(new Date(`${String(evento.fecha_evento).slice(0, 10)}T12:00:00Z`));
        root.querySelector('[data-event-location]').textContent = evento.ubicacion;
        root.querySelector('[data-event-guests]').textContent = String(evento.numero_invitados);

        grid.replaceChildren();
        if (!opciones.length) {
            const empty = document.createElement('p');
            empty.className = 'empty-state';
            empty.textContent = 'No hay propuestas compatibles dentro del 35 % del presupuesto por proveedor. Puedes continuar sin seleccionar proveedores.';
            grid.append(empty);
        }

        opciones.forEach((provider) => grid.append(createProviderCard(provider, totalLimit)));
        const selected = new Map((evento.proveedores_seleccionados ?? []).map((provider) => [provider.key, provider]));
        grid.querySelectorAll('[data-provider-select]').forEach((input) => {
            input.checked = selected.has(input.value);
        });
        root.querySelector('[data-provider-total]').textContent = money(
            Array.from(selected.values()).reduce((sum, item) => sum + Number(item.cost), 0),
        );
        grid.addEventListener('change', (event) => {
            const input = event.target.closest('[data-provider-select]');
            if (!input) return;
            const provider = opciones.find((item) => item.key === input.value);
            const nextTotal = Array.from(selected.values()).reduce((sum, item) => sum + item.cost, 0) + (input.checked ? provider.cost : 0);
            if (input.checked && nextTotal > totalLimit) {
                input.checked = false;
                feedback.textContent = `La selección no puede superar ${money(totalLimit)} (70 % del presupuesto).`;
                return;
            }
            feedback.textContent = '';
            input.checked ? selected.set(provider.key, provider) : selected.delete(provider.key);
            root.querySelector('[data-provider-total]').textContent = money(Array.from(selected.values()).reduce((sum, item) => sum + item.cost, 0));
        });

        root.querySelector('[data-provider-continue]').addEventListener('click', async (event) => {
            const button = event.currentTarget;
            button.disabled = true;
            feedback.textContent = '';
            try {
                const proveedores = Array.from(selected.values()).map((provider) => ({
                    key: provider.key,
                    name: provider.name,
                    category: provider.category,
                    cost: provider.cost,
                    delivery_status: 'Pendiente',
                    delivery_date: null,
                    phone: provider.phone,
                    email: provider.email,
                }));
                const save = await fetch(`${root.dataset.apiUrl}/${eventId}`, {
                    method: 'PUT',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ proveedores_seleccionados: proveedores }),
                });
                const result = await save.json().catch(() => ({}));
                if (!save.ok) throw new Error(Object.values(result.errors ?? {}).flat()[0] ?? result.message ?? 'No se pudieron guardar las propuestas.');
                window.location.assign(`/eventos/${eventId}#info`);
            } catch (error) {
                feedback.textContent = error.message;
                button.disabled = false;
            }
        });
    } catch (error) {
        feedback.textContent = error.message;
    }
}

/** @param {Record<string, any>} provider @param {number} totalLimit @returns {HTMLElement} */
function createProviderCard(provider, totalLimit) {
    const card = document.createElement('label');
    card.className = 'provider-option panel-card';
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.value = provider.key;
    checkbox.dataset.providerSelect = '';
    const title = document.createElement('strong');
    title.textContent = provider.name;
    const category = document.createElement('span');
    category.textContent = provider.category;
    const price = document.createElement('b');
    price.textContent = `${money(provider.cost)} estimado`;
    const note = document.createElement('small');
    note.textContent = `Máximo por proveedor: ${money(totalLimit / 2)}.`;
    card.append(checkbox, title, category, price, note);
    return card;
}

/** @param {string} value @returns {string} */
function normalize(value) {
    return String(value ?? '').toLocaleLowerCase('es-MX').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

/** @param {number|string} value @returns {string} */
function money(value) {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(Number(value) || 0);
}
