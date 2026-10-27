/**
 * @company Bigbitbyte
 * @author Ivan Hernández Hinojosa
 * @created 26/octubre/2026
 * @description Módulo de validaciones reutilizables para formularios.
 *   - Bloquea caracteres no numéricos en campos numéricos
 *   - Bloquea Ctrl+V / Cmd+V en todos los campos del formulario
 *   - Restringe fechas a un margen de ±2 años desde hoy
 *   - Limita longitudes de texto
 *   - Valida rangos numéricos realistas antes del envío
 */

/* ─────────────────────────────────────────────
   Constantes de configuración
───────────────────────────────────────────── */

/** Año en curso. */
const CURRENT_YEAR = new Date().getFullYear();

/** Margen de años permitido para fechas (hacia atrás y hacia adelante). */
const DATE_YEAR_MARGIN = 2;

/** Rangos realistas para campos numéricos del evento. */
const NUMERIC_RULES = {
    numero_invitados: { min: 1,   max: 5000,      label: 'Número de invitados', integer: true  },
    presupuesto:      { min: 500, max: 10_000_000, label: 'Presupuesto',         integer: false },
};

/** Longitudes máximas para campos de texto. */
const TEXT_MAX_LENGTHS = {
    nombre:     120,
    ubicacion:  160,
    reglamento: 500,
    // invitados
    correo:     160,
};

/* ─────────────────────────────────────────────
   Helpers internos
───────────────────────────────────────────── */

/**
 * Muestra o limpia el mensaje de error asociado a un campo.
 *
 * @param {HTMLInputElement|HTMLSelectElement} field
 * @param {string} message  - Vacío para limpiar el error.
 */
function setFieldError(field, message) {
    // Buscamos el contenedor de error más cercano al campo
    let errorEl = field.parentElement?.querySelector('.field-error');

    if (!errorEl) {
        errorEl = document.createElement('span');
        errorEl.className = 'field-error';
        errorEl.setAttribute('role', 'alert');
        field.after(errorEl);
    }

    if (message) {
        errorEl.textContent = message;
        field.setAttribute('aria-invalid', 'true');
        field.classList.add('input--error');
    } else {
        errorEl.textContent = '';
        field.removeAttribute('aria-invalid');
        field.classList.remove('input--error');
    }
}

/**
 * Devuelve la fecha mínima y máxima permitida en formato YYYY-MM-DD.
 *
 * @returns {{ min: string, max: string }}
 */
function getAllowedDateRange() {
    const min = new Date();
    min.setFullYear(min.getFullYear() - DATE_YEAR_MARGIN);

    const max = new Date();
    max.setFullYear(max.getFullYear() + DATE_YEAR_MARGIN);

    const fmt = (d) => d.toISOString().slice(0, 10);
    return { min: fmt(min), max: fmt(max) };
}

/* ─────────────────────────────────────────────
   Bloqueos de teclado
───────────────────────────────────────────── */

/**
 * Bloquea cualquier tecla que no corresponda a dígitos, punto decimal,
 * guión (negativo), o teclas de control (backspace, tab, flechas, etc.)
 * en un input numérico.
 *
 * @param {KeyboardEvent} event
 */
function blockNonNumericKeys(event) {
    const allowedKeys = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
        'Home', 'End',
    ];

    if (allowedKeys.includes(event.key)) return;
    if (event.ctrlKey || event.metaKey) {
        // Bloquar Ctrl+V / Cmd+V explícitamente
        if (event.key === 'v' || event.key === 'V') {
            event.preventDefault();
            return;
        }
        // Permitir Ctrl+A, Ctrl+C, Ctrl+X, Ctrl+Z
        return;
    }

    const field = /** @type {HTMLInputElement} */ (event.target);
    const allowDecimal = field.dataset.allowDecimal === 'true';

    // Permitir un punto decimal si el campo lo admite y no tiene ya uno
    if (allowDecimal && (event.key === '.' || event.key === ',')) {
        if (field.value.includes('.') || field.value.includes(',')) {
            event.preventDefault();
        }
        return;
    }

    // Bloquear cualquier cosa que no sea dígito
    if (!/^\d$/.test(event.key)) {
        event.preventDefault();
    }
}

/**
 * Bloquea Ctrl+V / Cmd+V en cualquier campo de texto para impedir
 * pegar contenido del portapapeles.
 *
 * @param {KeyboardEvent} event
 */
function blockPaste(event) {
    if ((event.ctrlKey || event.metaKey) && (event.key === 'v' || event.key === 'V')) {
        event.preventDefault();
    }
}

/**
 * Bloquea el evento nativo `paste` (pegado con clic derecho / menú contextual).
 *
 * @param {ClipboardEvent} event
 */
function blockPasteEvent(event) {
    event.preventDefault();
}

/* ─────────────────────────────────────────────
   Validaciones de rango y formato
───────────────────────────────────────────── */

/**
 * Valida el rango numérico de un campo según las reglas definidas en NUMERIC_RULES.
 * Devuelve el mensaje de error o cadena vacía si es válido.
 *
 * @param {string} fieldName
 * @param {string|number} rawValue
 * @returns {string}
 */
function validateNumericRange(fieldName, rawValue) {
    const rule = NUMERIC_RULES[fieldName];
    if (!rule) return '';

    const value = rule.integer ? parseInt(rawValue, 10) : parseFloat(rawValue);

    if (rawValue === '' || rawValue === null || rawValue === undefined) return '';
    if (isNaN(value)) return `${rule.label} debe ser un número válido.`;
    if (value < rule.min) return `${rule.label} no puede ser menor a ${rule.min.toLocaleString('es-MX')}.`;
    if (value > rule.max) return `${rule.label} no puede superar ${rule.max.toLocaleString('es-MX')}.`;

    return '';
}

/**
 * Valida que una fecha esté dentro del rango de ±2 años desde hoy.
 * Devuelve el mensaje de error o cadena vacía si es válida.
 *
 * @param {string} dateValue  - Formato YYYY-MM-DD
 * @returns {string}
 */
function validateDateRange(dateValue) {
    if (!dateValue) return '';

    const { min, max } = getAllowedDateRange();

    if (dateValue < min) {
        return `La fecha no puede ser anterior a ${formatDateLabel(min)}.`;
    }
    if (dateValue > max) {
        return `La fecha no puede ser posterior a ${formatDateLabel(max)}.`;
    }

    return '';
}

/**
 * Formatea una fecha YYYY-MM-DD a un texto legible en español.
 *
 * @param {string} isoDate
 * @returns {string}
 */
function formatDateLabel(isoDate) {
    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC',
    }).format(new Date(`${isoDate}T12:00:00Z`));
}

/**
 * Valida la longitud de un campo de texto.
 *
 * @param {string} fieldName
 * @param {string} value
 * @returns {string}
 */
function validateTextLength(fieldName, value) {
    const max = TEXT_MAX_LENGTHS[fieldName];
    if (!max) return '';
    if (value && value.length > max) {
        return `Máximo ${max} caracteres permitidos (actualmente ${value.length}).`;
    }
    return '';
}

/**
 * Valida formato de correo electrónico básico.
 *
 * @param {string} value
 * @returns {string}
 */
function validateEmail(value) {
    if (!value) return '';
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    if (!emailRegex.test(value)) {
        return 'Ingresa un correo electrónico válido (ej. usuario@dominio.com).';
    }
    return '';
}

/* ─────────────────────────────────────────────
   API pública: aplica validaciones a un formulario
───────────────────────────────────────────── */

/**
 * Aplica todos los listeners de validación a los campos de un formulario
 * de evento (creación/modal).
 *
 * @param {HTMLFormElement} form
 */
export function applyEventFormValidations(form) {
    if (!form) return;

    const { min, max } = getAllowedDateRange();

    // ── Campos numéricos ─────────────────────────────────────────────────
    const numInvitados = form.querySelector('[name="numero_invitados"]');
    const presupuesto  = form.querySelector('[name="presupuesto"]');

    if (numInvitados) {
        numInvitados.setAttribute('inputmode', 'numeric');
        numInvitados.addEventListener('keydown', blockNonNumericKeys);
        numInvitados.addEventListener('keydown', blockPaste);
        numInvitados.addEventListener('paste', blockPasteEvent);
        numInvitados.addEventListener('input', () => {
            // Eliminar cualquier carácter no numérico que se haya colado (ej. autocompletar del SO)
            numInvitados.value = numInvitados.value.replace(/[^0-9]/g, '');
            const err = validateNumericRange('numero_invitados', numInvitados.value);
            setFieldError(numInvitados, err);
        });
        numInvitados.addEventListener('blur', () => {
            const err = validateNumericRange('numero_invitados', numInvitados.value);
            setFieldError(numInvitados, err);
        });
    }

    if (presupuesto) {
        presupuesto.setAttribute('inputmode', 'decimal');
        presupuesto.dataset.allowDecimal = 'true';
        presupuesto.addEventListener('keydown', blockNonNumericKeys);
        presupuesto.addEventListener('keydown', blockPaste);
        presupuesto.addEventListener('paste', blockPasteEvent);
        presupuesto.addEventListener('input', () => {
            // Permitir solo dígitos y un punto decimal
            presupuesto.value = presupuesto.value.replace(/[^0-9.]/g, '').replace(/(\.\d*)\./g, '$1');
            const err = validateNumericRange('presupuesto', presupuesto.value);
            setFieldError(presupuesto, err);
        });
        presupuesto.addEventListener('blur', () => {
            const err = validateNumericRange('presupuesto', presupuesto.value);
            setFieldError(presupuesto, err);
        });
    }

    // ── Campo de fecha ───────────────────────────────────────────────────
    const fechaInput = form.querySelector('[name="fecha_evento"]');
    if (fechaInput) {
        fechaInput.setAttribute('min', min);
        fechaInput.setAttribute('max', max);
        fechaInput.addEventListener('keydown', blockPaste);
        fechaInput.addEventListener('paste', blockPasteEvent);
        fechaInput.addEventListener('change', () => {
            const err = validateDateRange(fechaInput.value);
            setFieldError(fechaInput, err);
        });
        fechaInput.addEventListener('blur', () => {
            const err = validateDateRange(fechaInput.value);
            setFieldError(fechaInput, err);
        });
    }

    // ── Campos de texto: bloqueo de pegado y longitud ────────────────────
    const textFields = [
        form.querySelector('[name="nombre"]'),
        form.querySelector('[name="ubicacion"]'),
        form.querySelector('[name="reglamento"]'),
    ];

    textFields.forEach((field) => {
        if (!field) return;
        const fieldName = field.name;

        field.addEventListener('keydown', blockPaste);
        field.addEventListener('paste', blockPasteEvent);
        field.addEventListener('input', () => {
            const err = validateTextLength(fieldName, field.value);
            setFieldError(field, err);
        });
        field.addEventListener('blur', () => {
            const err = validateTextLength(fieldName, field.value);
            setFieldError(field, err);
        });
    });
}

/**
 * Aplica todas las validaciones al formulario de invitados (pestaña show).
 *
 * @param {HTMLFormElement} form
 */
export function applyGuestFormValidations(form) {
    if (!form) return;

    // ── Nombre del invitado ──────────────────────────────────────────────
    const nombreField = form.querySelector('[name="nombre"]');
    if (nombreField) {
        nombreField.addEventListener('keydown', blockPaste);
        nombreField.addEventListener('paste', blockPasteEvent);
        nombreField.addEventListener('input', () => {
            const err = validateTextLength('nombre', nombreField.value);
            setFieldError(nombreField, err);
        });
        nombreField.addEventListener('blur', () => {
            const err = validateTextLength('nombre', nombreField.value);
            setFieldError(nombreField, err);
        });
    }

    // ── Correo ───────────────────────────────────────────────────────────
    const correoField = form.querySelector('[name="correo"]');
    if (correoField) {
        correoField.addEventListener('keydown', blockPaste);
        correoField.addEventListener('paste', blockPasteEvent);
        correoField.addEventListener('input', () => {
            const errLen = validateTextLength('correo', correoField.value);
            setFieldError(correoField, errLen || validateEmail(correoField.value));
        });
        correoField.addEventListener('blur', () => {
            const errLen = validateTextLength('correo', correoField.value);
            setFieldError(correoField, errLen || validateEmail(correoField.value));
        });
    }
}

/**
 * Ejecuta todas las validaciones sincrónicas sobre un formulario de evento
 * antes del envío. Devuelve `true` si el formulario es válido, `false` si hay errores.
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
export function validateEventFormBeforeSubmit(form) {
    let isValid = true;

    // Fecha
    const fechaInput = form.querySelector('[name="fecha_evento"]');
    if (fechaInput) {
        const err = validateDateRange(fechaInput.value);
        setFieldError(fechaInput, err);
        if (err) isValid = false;
    }

    // Numéricos
    ['numero_invitados', 'presupuesto'].forEach((name) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field || field.value === '') return;
        const err = validateNumericRange(name, field.value);
        setFieldError(field, err);
        if (err) isValid = false;
    });

    // Textos
    ['nombre', 'ubicacion', 'reglamento'].forEach((name) => {
        const field = form.querySelector(`[name="${name}"]`);
        if (!field) return;
        const err = validateTextLength(name, field.value);
        setFieldError(field, err);
        if (err) isValid = false;
    });

    return isValid;
}

/**
 * Ejecuta todas las validaciones sincrónicas sobre el formulario de invitado
 * antes del envío. Devuelve `true` si es válido, `false` si hay errores.
 *
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
export function validateGuestFormBeforeSubmit(form) {
    let isValid = true;

    const nombreField = form.querySelector('[name="nombre"]');
    if (nombreField) {
        const err = validateTextLength('nombre', nombreField.value);
        setFieldError(nombreField, err);
        if (err) isValid = false;
    }

    const correoField = form.querySelector('[name="correo"]');
    if (correoField) {
        const errLen = validateTextLength('correo', correoField.value);
        const errFmt = validateEmail(correoField.value);
        const err = errLen || errFmt;
        setFieldError(correoField, err);
        if (err) isValid = false;
    }

    return isValid;
}
