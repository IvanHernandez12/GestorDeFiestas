<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 25/noviembre/2026
 * @descripción Renderiza y organiza mesas, espacios y sillas reservadas en el plano del salón.
 */

import { computed, ref, watch } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    tables: {
        type: Array,
        default: () => [],
    },
    spaces: {
        type: Array,
        default: () => [],
    },
    guests: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'tables-change', 'spaces-change', 'guest-seat-change', 'guests-refresh']);
const canvasWidth = 900;
const shapeOptions = ['round', 'square', 'rectangle'];
const spaceTypeOptions = [
    { value: 'bar', label: 'Bar', color: '#fed7aa', stroke: '#ea580c', width: 180, height: 90 },
    { value: 'dance_floor', label: 'Pista de baile', color: '#ede9fe', stroke: '#7c3aed', width: 260, height: 180 },
    { value: 'gift_table', label: 'Mesa de regalos', color: '#fef3c7', stroke: '#d97706', width: 150, height: 90 },
    { value: 'stage', label: 'Escenario', color: '#bbf7d0', stroke: '#16a34a', width: 260, height: 80 },
    { value: 'catering', label: 'Catering', color: '#e0f2fe', stroke: '#0284c7', width: 180, height: 100 },
    { value: 'photo_zone', label: 'Zona de fotos', color: '#fce7f3', stroke: '#db2777', width: 160, height: 120 },
    { value: 'other', label: 'Otro', color: '#e2e8f0', stroke: '#475569', width: 160, height: 100 },
];
const pendingRequests = ref(0);
const isSaving = computed(() => pendingRequests.value > 0);
const saveError = ref('');
const selectedTableId = ref(null);
const selectedSpaceId = ref(null);
const newSpaceType = ref('bar');
const tableNodes = ref([]);
const spaceNodes = ref([]);

function normalizeTable(table, index) {
    const width = Number(table.width) || 76;
    const height = Number(table.height) || 76;
    const hasPosition = Number(table.position_x) !== 0 || Number(table.position_y) !== 0;

    return {
        ...table,
        shape: shapeOptions.includes(table.shape) ? table.shape : 'round',
        width,
        height,
        capacity: Number(table.capacity) || 8,
        x: hasPosition ? Number(table.position_x) : 100 + (index % 6) * 135,
        y: hasPosition ? Number(table.position_y) : 160 + Math.floor(index / 6) * 115,
    };
}

function syncTables(tables) {
    tableNodes.value = [...tables]
        .sort((firstTable, secondTable) => {
            const firstPriority = Number(firstTable.guest_category?.priority ?? Number.MAX_SAFE_INTEGER);
            const secondPriority = Number(secondTable.guest_category?.priority ?? Number.MAX_SAFE_INTEGER);

            return firstPriority - secondPriority || Number(firstTable.table_number) - Number(secondTable.table_number);
        })
        .map((table, index) => normalizeTable(table, index));
}

watch(() => props.tables, syncTables, { immediate: true });

function normalizeSpace(space, index) {
    const preset = spaceType(space.type);
    const width = Number(space.width) || preset.width;
    const height = Number(space.height) || preset.height;
    const hasPosition = Number(space.position_x) !== 0 || Number(space.position_y) !== 0;

    return {
        ...space,
        name: space.name || preset.label,
        type: preset.value,
        width,
        height,
        x: hasPosition ? Number(space.position_x) : 60 + (index % 3) * 280,
        y: hasPosition ? Number(space.position_y) : 170 + Math.floor(index / 3) * 170,
    };
}

function syncSpaces(spaces) {
    spaceNodes.value = spaces.map((space, index) => normalizeSpace(space, index));
}

watch(() => props.spaces, syncSpaces, { immediate: true });

const selectedTable = computed(() => tableNodes.value.find((table) => table.id === selectedTableId.value) ?? null);
const selectedSpace = computed(() => spaceNodes.value.find((space) => space.id === selectedSpaceId.value) ?? null);
const maxTableHeight = computed(() => tableNodes.value.reduce((maximum, table) => Math.max(maximum, Number(table.height) || 76), 76));
const canvasHeight = computed(() => {
    const tableRows = Math.ceil(tableNodes.value.length / 6) * (maxTableHeight.value + 39);
    const spaceBottom = spaceNodes.value.reduce((maximum, space) => Math.max(maximum, space.y + Number(space.height) / 2 + 40), 0);

    return Math.min(700, Math.max(600, 160 + tableRows, spaceBottom));
});

function emitTables() {
    emit('tables-change', tableNodes.value.map((table) => ({
        ...table,
        position_x: table.x,
        position_y: table.y,
    })));
}

function emitSpaces() {
    emit('spaces-change', spaceNodes.value.map((space) => ({
        ...space,
        position_x: space.x,
        position_y: space.y,
    })));
}

function spaceType(value) {
    return spaceTypeOptions.find((option) => option.value === value) ?? spaceTypeOptions[spaceTypeOptions.length - 1];
}

function guestForSeat(table, seatNumber) {
    return props.guests.find((guest) => Number(guest.table_id) === Number(table.id) && Number(guest.seat_number) === Number(seatNumber)) ?? null;
}

function getCookie(name) {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith(`${name}=`));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
}

function getValidationMessage(responseData) {
    return Object.values(responseData.errors ?? {})[0]?.[0]
        || responseData.message
        || 'No fue posible completar la operación.';
}

async function requestJson(url, method, body) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
    };

    if (body) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers,
        body: body ? JSON.stringify(body) : undefined,
    });
    const responseData = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(getValidationMessage(responseData));
    }

    return responseData;
}

async function savePosition(table, event) {
    const position = event.target.position();
    const previousX = table.x;
    const previousY = table.y;
    const nextX = Math.max(0, Math.min(1000, position.x));
    const nextY = Math.max(104, Math.min(700, position.y));

    table.x = nextX;
    table.y = nextY;
    saveError.value = '';
    pendingRequests.value++;

    try {
        const savedTable = await requestJson(`/events/${props.event.id}/tables/${table.id}/position`, 'PATCH', {
            position_x: nextX,
            position_y: nextY,
        });

        table.position_x = savedTable.position_x;
        table.position_y = savedTable.position_y;
        emitTables();
    } catch (error) {
        table.x = previousX;
        table.y = previousY;
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

async function patchTable(table, changes) {
    saveError.value = '';
    pendingRequests.value++;

    try {
        const savedTable = await requestJson(`/events/${props.event.id}/tables/${table.id}`, 'PATCH', changes);
        const index = tableNodes.value.findIndex((node) => node.id === savedTable.id);

        if (index !== -1) {
            tableNodes.value[index] = normalizeTable({ ...tableNodes.value[index], ...savedTable }, index);
        }

        emitTables();

        if (changes.capacity !== undefined) {
            emit('guests-refresh');
        }
    } catch (error) {
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

async function addTable() {
    const index = tableNodes.value.length;

    saveError.value = '';
    pendingRequests.value++;

    try {
        const savedTable = await requestJson(`/events/${props.event.id}/tables`, 'POST', {
            capacity: 8,
            shape: 'round',
            width: 76,
            height: 76,
            position_x: 100 + (index % 6) * 135,
            position_y: 160 + Math.floor(index / 6) * 115,
        });

        tableNodes.value.push(normalizeTable(savedTable, index));
        selectedTableId.value = savedTable.id;
        emitTables();
    } catch (error) {
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

async function deleteTable(table) {
    if (!window.confirm(`¿Eliminar la mesa ${table.table_number} del plano?`)) {
        return;
    }

    saveError.value = '';
    pendingRequests.value++;

    try {
        await requestJson(`/events/${props.event.id}/tables/${table.id}`, 'DELETE');
        tableNodes.value = tableNodes.value.filter((node) => node.id !== table.id);

        if (selectedTableId.value === table.id) {
            selectedTableId.value = null;
        }

        emitTables();
        emit('guests-refresh');
    } catch (error) {
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

async function saveSpacePosition(space, event) {
    const position = event.target.position();
    const previousX = space.x;
    const previousY = space.y;
    const nextX = Math.max(0, Math.min(1000, position.x));
    const nextY = Math.max(104, Math.min(700, position.y));

    space.x = nextX;
    space.y = nextY;
    saveError.value = '';
    pendingRequests.value++;

    try {
        const savedSpace = await requestJson(`/events/${props.event.id}/spaces/${space.id}`, 'PATCH', {
            position_x: nextX,
            position_y: nextY,
        });
        const index = spaceNodes.value.findIndex((node) => node.id === savedSpace.id);

        if (index !== -1) {
            spaceNodes.value[index] = normalizeSpace({ ...spaceNodes.value[index], ...savedSpace }, index);
        }

        emitSpaces();
    } catch (error) {
        space.x = previousX;
        space.y = previousY;
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

async function patchSpace(space, changes) {
    saveError.value = '';
    pendingRequests.value++;

    try {
        const savedSpace = await requestJson(`/events/${props.event.id}/spaces/${space.id}`, 'PATCH', changes);
        const index = spaceNodes.value.findIndex((node) => node.id === savedSpace.id);

        if (index !== -1) {
            spaceNodes.value[index] = normalizeSpace({ ...spaceNodes.value[index], ...savedSpace }, index);
        }

        emitSpaces();
    } catch (error) {
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

function changeSpaceType(space, event) {
    const preset = spaceType(event.target.value);

    patchSpace(space, { type: preset.value, width: preset.width, height: preset.height });
}

async function addSpace() {
    const preset = spaceType(newSpaceType.value);
    const index = spaceNodes.value.length;

    saveError.value = '';
    pendingRequests.value++;

    try {
        const savedSpace = await requestJson(`/events/${props.event.id}/spaces`, 'POST', {
            name: preset.label,
            type: preset.value,
            width: preset.width,
            height: preset.height,
            position_x: 60 + (index % 3) * 280,
            position_y: 170 + Math.floor(index / 3) * 170,
        });

        spaceNodes.value.push(normalizeSpace(savedSpace, index));
        selectedSpaceId.value = savedSpace.id;
        selectedTableId.value = null;
        emitSpaces();
    } catch (error) {
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

async function deleteSpace(space) {
    if (!window.confirm(`¿Eliminar el espacio "${space.name}" del plano?`)) {
        return;
    }

    saveError.value = '';
    pendingRequests.value++;

    try {
        await requestJson(`/events/${props.event.id}/spaces/${space.id}`, 'DELETE');
        spaceNodes.value = spaceNodes.value.filter((node) => node.id !== space.id);

        if (selectedSpaceId.value === space.id) {
            selectedSpaceId.value = null;
        }

        emitSpaces();
    } catch (error) {
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

async function assignSeat(table, seatNumber, event) {
    const targetGuestId = event.target.value ? Number(event.target.value) : null;
    const currentGuest = guestForSeat(table, seatNumber);
    const guestId = targetGuestId ?? currentGuest?.id;

    if (!guestId) {
        return;
    }

    saveError.value = '';
    pendingRequests.value++;

    try {
        const response = await requestJson(`/api/events/${props.event.id}/guests/${guestId}/seat`, 'PATCH', targetGuestId
            ? { table_id: table.id, seat_number: seatNumber }
            : { table_id: null, seat_number: null });

        emit('guest-seat-change', response.guest);
        emit('guests-refresh');
    } catch (error) {
        saveError.value = error.message;
    } finally {
        pendingRequests.value--;
    }
}

function tableConfig(table) {
    const width = Number(table.width) || 76;
    const height = Number(table.height) || 76;
    const isSelected = selectedTableId.value === table.id;
    const isPriority = Number(table.guest_category?.priority) === 1;

    return {
        x: table.x,
        y: table.y,
        fill: isPriority ? '#dbeafe' : '#ede9fe',
        stroke: isSelected ? '#f59e0b' : (isPriority ? '#2563eb' : '#7c3aed'),
        strokeWidth: isSelected ? 5 : 3,
        draggable: true,
        dragBoundFunc: (position) => ({
            x: Math.max(width / 2, Math.min(canvasWidth - width / 2, position.x)),
            y: Math.max(104 + height / 2, Math.min(canvasHeight.value - height / 2, position.y)),
        }),
    };
}

function labelConfig(table) {
    const width = Number(table.width) || 76;

    return {
        x: table.x - width / 2 + 6,
        y: table.y - 12,
        width: width - 12,
        text: `Mesa ${table.table_number}\n${table.capacity} personas`,
        align: 'center',
        fontSize: 11,
        lineHeight: 1.35,
        fill: '#0f172a',
        listening: false,
    };
}

function spaceConfig(space) {
    const preset = spaceType(space.type);
    const isSelected = selectedSpaceId.value === space.id;

    return {
        x: space.x,
        y: space.y,
        width: Number(space.width) || preset.width,
        height: Number(space.height) || preset.height,
        offsetX: (Number(space.width) || preset.width) / 2,
        offsetY: (Number(space.height) || preset.height) / 2,
        fill: preset.color,
        stroke: isSelected ? '#f59e0b' : preset.stroke,
        strokeWidth: isSelected ? 5 : 2,
        cornerRadius: 10,
        draggable: true,
        dragBoundFunc: (position) => ({
            x: Math.max((Number(space.width) || preset.width) / 2, Math.min(canvasWidth - (Number(space.width) || preset.width) / 2, position.x)),
            y: Math.max(104 + (Number(space.height) || preset.height) / 2, Math.min(canvasHeight.value - (Number(space.height) || preset.height) / 2, position.y)),
        }),
    };
}

function spaceLabelConfig(space) {
    const width = Number(space.width) || spaceType(space.type).width;

    return {
        x: space.x - width / 2 + 6,
        y: space.y - 8,
        width: width - 12,
        text: space.name,
        align: 'center',
        fontSize: 12,
        fontStyle: 'bold',
        fill: '#0f172a',
        listening: false,
    };
}

function seatConfig(table, seatNumber, guest) {
    const capacity = Math.max(Number(table.capacity) || 8, 1);
    const angle = ((seatNumber - 1) / capacity) * Math.PI * 2 - Math.PI / 2;
    const radiusX = Number(table.width) / 2 + 20;
    const radiusY = Number(table.height) / 2 + 20;
    const isOccupied = guest !== null;

    return {
        x: table.x + Math.cos(angle) * radiusX,
        y: table.y + Math.sin(angle) * radiusY,
        radius: 9,
        fill: isOccupied ? '#10b981' : '#ffffff',
        stroke: isOccupied ? '#047857' : '#94a3b8',
        strokeWidth: 2,
    };
}

function seatLabelConfig(table, seatNumber, guest) {
    const config = seatConfig(table, seatNumber, guest);

    return {
        x: config.x - 5,
        y: config.y - 6,
        width: 10,
        text: String(seatNumber),
        align: 'center',
        fontSize: 9,
        fontStyle: 'bold',
        fill: guest !== null ? '#ffffff' : '#475569',
        listening: false,
    };
}
</script>

<template>
    <section class="space-y-5" aria-label="Lienzo del salón">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-blue-600">Distribución del salón</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ event.name }}</h2>
                <p class="mt-1 text-sm text-slate-500">Edita mesas, espacios y sillas; los cambios se guardan al instante.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <select
                    v-model="newSpaceType"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                    aria-label="Tipo de espacio a agregar"
                >
                    <option v-for="option in spaceTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
                <button
                    type="button"
                    class="rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                    :disabled="isSaving"
                    @click="addSpace"
                >Agregar espacio</button>
                <button
                    type="button"
                    class="rounded-lg bg-blue-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                    :disabled="isSaving"
                    @click="addTable"
                >Agregar mesa</button>
                <p class="text-xs text-slate-500" aria-live="polite">{{ isSaving ? 'Guardando…' : 'Los cambios se guardan automáticamente.' }}</p>
                <button type="button" class="grid size-10 place-items-center rounded-full bg-slate-100 text-xl text-slate-600 hover:bg-slate-200" aria-label="Cerrar lienzo" @click="emit('close')">×</button>
            </div>
        </div>

        <p v-if="saveError" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">{{ saveError }}</p>

        <div class="overflow-auto rounded-2xl border border-slate-200 bg-slate-100 p-3">
            <v-stage :config="{ width: canvasWidth, height: canvasHeight }" aria-label="Plano interactivo del salón">
                <v-layer>
                    <template v-for="space in spaceNodes" :key="`space-${space.id}`">
                        <v-rect
                            :config="spaceConfig(space)"
                            @click="selectedSpaceId = space.id; selectedTableId = null"
                            @dragend="saveSpacePosition(space, $event)"
                        />
                        <v-text :config="spaceLabelConfig(space)" />
                    </template>

                    <v-rect :config="{ x: 300, y: 24, width: 300, height: 54, fill: '#15803d', cornerRadius: 12 }" />
                    <v-text :config="{ x: 300, y: 41, width: 300, text: 'ESCENARIO', align: 'center', fontSize: 18, fontStyle: 'bold', fill: '#ffffff' }" />

                    <template v-for="table in tableNodes" :key="`table-${table.id}`">
                        <v-circle
                            v-if="table.shape === 'round'"
                            :config="{ ...tableConfig(table), radius: Number(table.width) / 2 }"
                            @click="selectedTableId = table.id; selectedSpaceId = null"
                            @dragend="savePosition(table, $event)"
                        />
                        <v-rect
                            v-else
                            :config="{ ...tableConfig(table), width: Number(table.width), height: Number(table.height), offsetX: Number(table.width) / 2, offsetY: Number(table.height) / 2, cornerRadius: 8 }"
                            @click="selectedTableId = table.id; selectedSpaceId = null"
                            @dragend="savePosition(table, $event)"
                        />
                        <template v-for="seat in table.capacity" :key="`seat-${table.id}-${seat}`">
                            <v-circle :config="seatConfig(table, seat, guestForSeat(table, seat))" />
                            <v-text :config="seatLabelConfig(table, seat, guestForSeat(table, seat))" />
                        </template>
                        <v-text :config="labelConfig(table)" />
                    </template>
                </v-layer>
            </v-stage>
        </div>

        <div v-if="selectedSpace" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-slate-900">Espacio: {{ selectedSpace.name }}</p>
                    <p class="text-xs text-slate-500">Los cambios se guardan al modificar cada campo.</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                    :disabled="isSaving"
                    @click="deleteSpace(selectedSpace)"
                >Eliminar espacio</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="space-y-1.5 text-sm font-semibold text-slate-700">Nombre
                    <input
                        type="text"
                        maxlength="100"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        :value="selectedSpace.name"
                        :disabled="isSaving"
                        @change="patchSpace(selectedSpace, { name: $event.target.value })"
                    >
                </label>
                <label class="space-y-1.5 text-sm font-semibold text-slate-700">Tipo
                    <select
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        :value="selectedSpace.type"
                        :disabled="isSaving"
                        @change="changeSpaceType(selectedSpace, $event)"
                    >
                        <option v-for="option in spaceTypeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                </label>
                <label class="space-y-1.5 text-sm font-semibold text-slate-700">Ancho
                    <input
                        type="number"
                        min="60"
                        max="600"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        :value="selectedSpace.width"
                        :disabled="isSaving"
                        @change="patchSpace(selectedSpace, { width: Number($event.target.value) })"
                    >
                </label>
                <label class="space-y-1.5 text-sm font-semibold text-slate-700">Alto
                    <input
                        type="number"
                        min="60"
                        max="600"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        :value="selectedSpace.height"
                        :disabled="isSaving"
                        @change="patchSpace(selectedSpace, { height: Number($event.target.value) })"
                    >
                </label>
            </div>
        </div>

        <div v-if="selectedTable" class="space-y-4 rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-slate-900">Mesa {{ selectedTable.table_number }}</p>
                    <p class="text-xs text-slate-500">Los cambios se guardan al modificar cada campo.</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                    :disabled="isSaving"
                    @click="deleteTable(selectedTable)"
                >Eliminar mesa</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="space-y-1.5 text-sm font-semibold text-slate-700">Forma
                    <select
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        :value="selectedTable.shape"
                        :disabled="isSaving"
                        @change="patchTable(selectedTable, { shape: $event.target.value })"
                    >
                        <option value="round">Redonda</option>
                        <option value="square">Cuadrada</option>
                        <option value="rectangle">Rectangular</option>
                    </select>
                </label>
                <label class="space-y-1.5 text-sm font-semibold text-slate-700">Aforo
                    <select
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        :value="selectedTable.capacity"
                        :disabled="isSaving"
                        @change="patchTable(selectedTable, { capacity: Number($event.target.value) })"
                    >
                        <option v-for="seat in 8" :key="seat" :value="seat">{{ seat }} personas</option>
                    </select>
                </label>
                <label v-if="selectedTable.shape === 'round'" class="space-y-1.5 text-sm font-semibold text-slate-700">Diámetro
                    <input
                        type="number"
                        min="40"
                        max="200"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                        :value="selectedTable.width"
                        :disabled="isSaving"
                        @change="patchTable(selectedTable, { width: Number($event.target.value), height: Number($event.target.value) })"
                    >
                </label>
                <template v-else>
                    <label class="space-y-1.5 text-sm font-semibold text-slate-700">Ancho
                        <input
                            type="number"
                            min="40"
                            max="200"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                            :value="selectedTable.width"
                            :disabled="isSaving"
                            @change="patchTable(selectedTable, { width: Number($event.target.value) })"
                        >
                    </label>
                    <label class="space-y-1.5 text-sm font-semibold text-slate-700">Alto
                        <input
                            type="number"
                            min="40"
                            max="200"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                            :value="selectedTable.height"
                            :disabled="isSaving"
                            @change="patchTable(selectedTable, { height: Number($event.target.value) })"
                        >
                    </label>
                </template>
            </div>
            <div class="border-t border-slate-100 pt-4">
                <p class="text-sm font-semibold text-slate-700">Asientos reservados</p>
                <p class="mt-1 text-xs text-slate-500">Elige al invitado de cada silla; verde significa reservado.</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <label v-for="seat in selectedTable.capacity" :key="seat" class="space-y-1 text-xs font-semibold text-slate-600">
                        Silla {{ seat }}
                        <select
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50"
                            :value="guestForSeat(selectedTable, seat)?.id ?? ''"
                            :disabled="isSaving"
                            @change="assignSeat(selectedTable, seat, $event)"
                        >
                            <option value="">Libre</option>
                            <option v-for="guest in guests" :key="guest.id" :value="guest.id">{{ guest.name }}</option>
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <p v-if="!tableNodes.length" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Este evento todavía no tiene mesas distribuidas.</p>
    </section>
</template>
