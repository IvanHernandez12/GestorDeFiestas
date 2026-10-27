/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa
 * @created 26/octubre/2026
 * @description Módulo de validaciones para llenado de formularios.
 *
 *  - Campos numéricos: sanitizan el valor en tiempo real (eliminan letras,
 *    recortan al máximo permitido) y no permiten pegar.
 *  - Campos de texto: consumen al límite de caracteres.
 *  - Fechas: el atributo min/max del input las restringe una fecha adecuada;
 *  - Todos los campos: bloquean paste por los tres vectores posibles
 *    (evento paste, keydown Ctrl/Cmd+V, contextmenu con opción pegar).
 */

/* ─────────────────────────────────────────────
   Configuración
───────────────────────────────────────────── */

/** Margen de años permitido para fechas (hacia atrás y hacia adelante). */
const DATE_YEAR_MARGIN = 2;

/**
 * Rangos y límites para campos numéricos.
 * integer:true → solo enteros; false → permite decimal.
 * maxDigits: máximo de dígitos totales que puede escribir el usuario
 *            antes del punto (para recorte inmediato).
 */
const NUMERIC_RULES = {
    numero_invitados: {
        min: 1, max: 5000, label: 'Número de invitados',
        integer: true, maxDigits: 4,          // 5000 → 4 dígitos
    },
    presupuesto: {
        min: 500, max: 10_000_000, label: 'Presupuesto',
        integer: false, maxDigits: 8,          // 10000000 → 8 dígitos
    },
};

/** Longitudes máximas para campos de texto. */
const TEXT_MAX_LENGTHS = {
    nombre:     120,
    ubicacion:  160,
    reglamento: 500,
    correo:     160,
};

/* ─────────────────────────────────────────────
   Helpers de UI
───────────────────────────────────────────── */

/**
 * Inserta o actualiza el mensaje de error debajo del campo.
 * Pasa message vacío para limpiar.
 *
 * @param {HTMLElement} field
 * @param {string} message
 */
function setFieldError(field, message) {
    let el = field.parentElement?.querySelector('.field-error');
    if (!el) {
        el = document.createElement('span');
        el.className = 'field-error';
        el.setAttribute('role', 'alert');
        field.after(el);
    }
    el.textContent = message;
    field.setAttribute('aria-invalid', message ? 'true' : 'false');
    field.classList.toggle('input--error', Boolean(message));
}

/**
 * Devuelve las fechas mínima y máxima permitidas en formato YYYY-MM-DD.
 *
 * @returns {{ min: string, max: string }}
 */
function getAllowedDateRange() {
    const toISO = (d) => d.toISOString().slice(0, 10);
    const min = new Date();
    min.setFullYear(min.getFullYear() - DATE_YEAR_MARGIN);
    const max = new Date();
    max.setFullYear(max.getFullYear() + DATE_YEAR_MARGIN);
    return { min: toISO(min), max: toISO(max) };
}

/**
 * Formatea YYYY-MM-DD a texto legible en español.
 *
 * @param {string} isoDate
 * @returns {string}
 */
function fmtDate(isoDate) {
    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC',
    }).format(new Date(`${isoDate}T12:00:00Z`));
}

/* ─────────────────────────────────────────────
   Bloqueo universal de pegado
───────────────────────────────────────────── */

/**
 * Bloquea los tres vectores de pegado en un elemento:
 *  1. Evento  (Ctrl+V, Cmd+V, clic derecho → Pegar)
 *  2. keydown con Ctrl/Cmd + V como respaldo
 *  3. contextmenu: deshabilita el menú contextual para evitar "Pegar"
 *
 * @param {HTMLElement} field
 */
function blockAllPaste(field) {
    field.addEventListener('paste', (e) => {
        e.preventDefault();
        e.stopPropagation();
    });

    field.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'v' || e.key === 'V')) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    field.addEventListener('contextmenu', (e) => {
        e.preventDefault();
    });
}

/* ─────────────────────────────────────────────
   Sanitizadores en tiempo real
───────────────────────────────────────────── */

/**
 * Sanitiza un campo numérico entero:
 *  - Elimina todo lo que no sea dígito
 *  - Recorta al número de dígitos máximo permitido
 *  - Si el valor supera el máximo numérico, lo reemplaza por el máximo
 *
 * @param {HTMLInputElement} field
 * @param {string} fieldName
 */
function sanitizeInteger(field, fieldName) {
    const rule = NUMERIC_RULES[fieldName];

    // 1. Quitar todo lo que no sea dígito
    let val = field.value.replace(/\D/g, '');

    // 2. Recortar al número máximo de dígitos
    if (val.length > rule.maxDigits) {
        val = val.slice(0, rule.maxDigits);
    }

    // 3. Si el número resultante supera el máximo, reemplazar por el máximo
    if (val !== '' && parseInt(val, 10) > rule.max) {
        val = String(rule.max);
    }

    field.value = val;

    // 4. Mostrar error si está por debajo del mínimo (no recortamos hacia arriba el mínimo)
    if (val !== '' && parseInt(val, 10) < rule.min) {
        setFieldError(field, `${rule.label} no puede ser menor a ${rule.min.toLocaleString('es-MX')}.`);
    } else {
        setFieldError(field, '');
    }
}

/**
 * Sanitiza un campo numérico decimal:
 *  - Elimina todo lo que no sea dígito o punto
 *  - Permite solo un punto decimal
 *  - Recorta la parte entera al máximo de dígitos
 *  - Si el valor supera el máximo numérico, lo reemplaza por el máximo
 *
 * @param {HTMLInputElement} field
 * @param {string} fieldName
 */
function sanitizeDecimal(field, fieldName) {
    const rule = NUMERIC_RULES[fieldName];

    // 1. Quitar todo lo que no sea dígito o punto
    let val = field.value.replace(/[^0-9.]/g, '');

    // 2. Permitir solo el primer punto decimal
    const parts = val.split('.');
    if (parts.length > 2) {
        val = parts[0] + '.' + parts.slice(1).join('');
    }

    // 3. Recortar la parte entera al máximo de dígitos
    if (parts[0].length > rule.maxDigits) {
        const decimals = parts[1] !== undefined ? '.' + parts[1] : '';
        val = parts[0].slice(0, rule.maxDigits) + decimals;
    }

    // 4. Si el valor completo supera el máximo, reemplazar
    const numeric = parseFloat(val);
    if (!isNaN(numeric) && numeric > rule.max) {
        val = String(rule.max);
    }

    field.value = val;

    if (val !== '' && !isNaN(numeric) && numeric < rule.min) {
        setFieldError(field, `${rule.label} no puede ser menor a ${rule.min.toLocaleString('es-MX')}.`);
    } else {
        setFieldError(field, '');
    }
}

/**
 * Sanitiza un campo de texto truncándolo al límite de caracteres.
 *
 * @param {HTMLInputElement|HTMLTextAreaElement} field
 * @param {string} fieldName
 */
function sanitizeText(field, fieldName) {
    const max = TEXT_MAX_LENGTHS[fieldName];
    if (!max) return;

    if (field.value.length > max) {
        field.value = field.value.slice(0, max);
        setFieldError(field, `Máximo ${max} caracteres permitidos.`);
    } else {
        setFieldError(field, '');
    }
}

/**
 * Valida la fecha e informa si está fuera del rango.
 * El input nativo ya bloquea el calendario, pero JS recubre el caso
 * en que el usuario escriba manualmente el año.
 *
 * @param {HTMLInputElement} field
 */
function validateDate(field) {
    const { min, max } = getAllowedDateRange();
    const val = field.value;

    if (!val) { setFieldError(field, ''); return; }

    if (val < min) {
        // Corregir el valor directamente al mínimo
        field.value = min;
        setFieldError(field, `Fecha ajustada al mínimo permitido: ${fmtDate(min)}.`);
    } else if (val > max) {
        field.value = max;
        setFieldError(field, `Fecha ajustada al máximo permitido: ${fmtDate(max)}.`);
    } else {
        setFieldError(field, '');
    }
}

/* ─────────────────────────────────────────────
   API pública
───────────────────────────────────────────── */

/**
 * Engancha todas las validaciones al formulario de creación de evento.
 *
 * @param {HTMLFormElement} form
 */
export function applyEventFormValidations(form) {
    if (!form) return;

    const { min, max } = getAllowedDateRange();

    /* ── Número de invitados (entero) ── */
    const numInvitados = form.querySelector('[name="numero_invitados"]');
    if (numInvitados) {
        numInvitados.setAttribute('inputmode', 'numeric');
        numInvitados.setAttribute('autocomplete', 'off');
        blockAllPaste(numInvitados);
        numInvitados.addEventListener('keydown', (e) => {
            // Bloquear teclas que no son dígitos ni control
            const nav = ['Backspace','Delete','Tab','Escape','Enter',
                         'ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
            if (nav.includes(e.key) || e.ctrlKey || e.metaKey) return;
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
        numInvitados.addEventListener('input', () => sanitizeInteger(numInvitados, 'numero_invitados'));
        numInvitados.addEventListener('blur',  () => sanitizeInteger(numInvitados, 'numero_invitados'));
        numInvitados.addEventListener('drop',  (e) => e.preventDefault());
    }

    /* ── Presupuesto (decimal) ── */
    const presupuesto = form.querySelector('[name="presupuesto"]');
    if (presupuesto) {
        presupuesto.setAttribute('inputmode', 'decimal');
        presupuesto.setAttribute('autocomplete', 'off');
        blockAllPaste(presupuesto);
        presupuesto.addEventListener('keydown', (e) => {
            const nav = ['Backspace','Delete','Tab','Escape','Enter',
                         'ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End'];
            if (nav.includes(e.key) || e.ctrlKey || e.metaKey) return;
            // Permitir un solo punto decimal
            if (e.key === '.') {
                if (presupuesto.value.includes('.')) e.preventDefault();
                return;
            }
            if (!/^\d$/.test(e.key)) e.preventDefault();
        });
        presupuesto.addEventListener('input', () => sanitizeDecimal(presupuesto, 'presupuesto'));
        presupuesto.addEventListener('blur',  () => sanitizeDecimal(presupuesto, 'presupuesto'));
        presupuesto.addEventListener('drop',  (e) => e.preventDefault());
    }

    /* ── Fecha del evento ── */
    const fechaInput = form.querySelector('[name="fecha_evento"]');
    if (fechaInput) {
        fechaInput.setAttribute('min', min);
        fechaInput.setAttribute('max', max);
        blockAllPaste(fechaInput);
        fechaInput.addEventListener('change', () => validateDate(fechaInput));
        fechaInput.addEventListener('blur',   () => validateDate(fechaInput));
    }

    /* ── Campos de texto con límite de caracteres ── */
    [
        { name: 'nombre',     maxlength: TEXT_MAX_LENGTHS.nombre     },
        { name: 'ubicacion',  maxlength: TEXT_MAX_LENGTHS.ubicacion  },
        { name: 'reglamento', maxlength: TEXT_MAX_LENGTHS.reglamento },
    ].forEach(({ name, maxlength }) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        field.setAttribute('maxlength', String(maxlength));
        field.setAttribute('autocomplete', 'off');
        blockAllPaste(field);
        field.addEventListener('input', () => sanitizeText(field, name));
        field.addEventListener('drop',  (e) => e.preventDefault());
    });
}

/**
 * Engancha todas las validaciones al formulario de invitados.
 *
 * @param {HTMLFormElement} form
 */
export function applyGuestFormValidations(form) {
    if (!form) return;

    /* ── Nombre ── */
    const nombreField = form.querySelector('[name="nombre"]');
    if (nombreField) {
        nombreField.setAttribute('maxlength', String(TEXT_MAX_LENGTHS.nombre));
        nombreField.setAttribute('autocomplete', 'off');
        blockAllPaste(nombreField);
        nombreField.addEventListener('input', () => sanitizeText(nombreField, 'nombre'));
        nombreField.addEventListener('drop',  (e) => e.preventDefault());
    }

    /* ── Correo ── */
    const correoField = form.querySelector('[name="correo"]');
    if (correoField) {
        correoField.setAttribute('maxlength', String(TEXT_MAX_LENGTHS.correo));
        correoField.setAttribute('autocomplete', 'off');
        blockAllPaste(correoField);
        correoField.addEventListener('input', () => sanitizeText(correoField, 'correo'));
        correoField.addEventListener('blur', () => {
            if (!correoField.value) return;
            const ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correoField.value);
            setFieldError(correoField, ok ? '' : 'Correo no válido (ej. usuario@dominio.com).');
        });
        correoField.addEventListener('drop', (e) => e.preventDefault());
    }
}

/**
 * Valida el formulario de evento completo antes del envío.
 * Retorna true si todo es válido.
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
export function validateEventFormBeforeSubmit(form) {
    let ok = true;

    // Fecha
    const fecha = form.querySelector('[name="fecha_evento"]');
    if (fecha) {
        validateDate(fecha);
        if (fecha.getAttribute('aria-invalid') === 'true') ok = false;
    }

    // Numéricos
    const numInv = form.querySelector('[name="numero_invitados"]');
    if (numInv && numInv.value !== '') {
        sanitizeInteger(numInv, 'numero_invitados');
        if (numInv.getAttribute('aria-invalid') === 'true') ok = false;
    }

    const presup = form.querySelector('[name="presupuesto"]');
    if (presup && presup.value !== '') {
        sanitizeDecimal(presup, 'presupuesto');
        if (presup.getAttribute('aria-invalid') === 'true') ok = false;
    }

    // Textos
    ['nombre', 'ubicacion', 'reglamento'].forEach((name) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (field) {
            sanitizeText(field, name);
            if (field.getAttribute('aria-invalid') === 'true') ok = false;
        }
    });

    return ok;
}

/**
 * Valida el formulario de invitado completo antes del envío.
 * Retorna true si todo es válido.
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
export function validateGuestFormBeforeSubmit(form) {
    let ok = true;

    const nombre = form.querySelector('[name="nombre"]');
    if (nombre) {
        sanitizeText(nombre, 'nombre');
        if (nombre.getAttribute('aria-invalid') === 'true') ok = false;
    }

    const correo = form.querySelector('[name="correo"]');
    if (correo) {
        sanitizeText(correo, 'correo');
        const valid = !correo.value || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(correo.value);
        if (!valid) {
            setFieldError(correo, 'Correo no válido (ej. usuario@dominio.com).');
            ok = false;
        }
        if (correo.getAttribute('aria-invalid') === 'true') ok = false;
    }

    return ok;
}
