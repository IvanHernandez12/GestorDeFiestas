<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Renderiza y permite organizar las mesas en el plano del salón.
 */

import { computed, reactive, ref } from 'vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    tables: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);
const canvasWidth = 900;
const isSaving = ref(false);
const saveError = ref('');

const orderedTables = computed(() => [...props.tables].sort((firstTable, secondTable) => {
    const firstPriority = Number(firstTable.guest_category?.priority ?? Number.MAX_SAFE_INTEGER);
    const secondPriority = Number(secondTable.guest_category?.priority ?? Number.MAX_SAFE_INTEGER);

    return firstPriority - secondPriority || Number(firstTable.table_number) - Number(secondTable.table_number);
}));

const canvasHeight = Math.max(600, 160 + Math.ceil(orderedTables.value.length / 6) * 115);
const tableNodes = reactive(orderedTables.value.map((table, index) => {
    const hasPosition = Number(table.position_x) !== 0 || Number(table.position_y) !== 0;

    return {
        ...table,
        x: hasPosition ? Number(table.position_x) : 100 + (index % 6) * 135,
        y: hasPosition ? Number(table.position_y) : 160 + Math.floor(index / 6) * 115,
    };
}));

function getCookie(name) {
    const cookie = document.cookie.split('; ').find((item) => item.startsWith(`${name}=`));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
}

async function savePosition(table, event) {
    const node = event.target;
    const position = node.position();

    table.x = position.x;
    table.y = position.y;
    saveError.value = '';
    isSaving.value = true;

    try {
        const response = await fetch(`/events/${props.event.id}/tables/${table.id}/position`, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
            },
            body: JSON.stringify({
                position_x: position.x,
                position_y: position.y,
            }),
        });

        if (!response.ok) {
            throw new Error('No se pudo guardar la posición de la mesa.');
        }
    } catch (error) {
        saveError.value = error.message;
    } finally {
        isSaving.value = false;
    }
}
</script>

<template>
    <section class="space-y-5" aria-label="Lienzo del salón">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-blue-600">Distribución del salón</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ event.name }}</h2>
                <p class="mt-1 text-sm text-slate-500">Las mesas de prioridad alta aparecen más cerca del escenario.</p>
            </div>
            <div class="flex items-center gap-3">
                <p class="text-xs text-slate-500" aria-live="polite">{{ isSaving ? 'Guardando…' : 'Los cambios se guardan al soltar cada mesa.' }}</p>
                <button type="button" class="grid size-10 place-items-center rounded-full bg-slate-100 text-xl text-slate-600 hover:bg-slate-200" aria-label="Cerrar lienzo" @click="emit('close')">×</button>
            </div>
        </div>

        <p v-if="saveError" class="rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert">{{ saveError }}</p>

        <div class="overflow-auto rounded-2xl border border-slate-200 bg-slate-100 p-3">
            <v-stage :config="{ width: canvasWidth, height: canvasHeight }" aria-label="Plano interactivo de mesas">
                <v-layer>
                    <v-rect :config="{ x: 300, y: 24, width: 300, height: 54, fill: '#15803d', cornerRadius: 12 }" />
                    <v-text :config="{ x: 300, y: 41, width: 300, text: 'ESCENARIO', align: 'center', fontSize: 18, fontStyle: 'bold', fill: '#ffffff' }" />

                    <template v-for="table in tableNodes" :key="table.id">
                        <v-circle
                            :config="{
                                x: table.x,
                                y: table.y,
                                radius: 38,
                                fill: Number(table.guest_category?.priority) === 1 ? '#dbeafe' : '#ede9fe',
                                stroke: Number(table.guest_category?.priority) === 1 ? '#2563eb' : '#7c3aed',
                                strokeWidth: 3,
                                draggable: true,
                                dragBoundFunc: (position) => ({
                                    x: Math.max(42, Math.min(canvasWidth - 42, position.x)),
                                    y: Math.max(104, Math.min(canvasHeight - 42, position.y)),
                                }),
                            }"
                            @dragend="savePosition(table, $event)"
                        />
                        <v-text :config="{ x: table.x - 32, y: table.y - 12, width: 64, text: `Mesa ${table.table_number}\n${table.capacity} personas`, align: 'center', fontSize: 11, lineHeight: 1.35, fill: '#0f172a', listening: false }" />
                    </template>
                </v-layer>
            </v-stage>
        </div>

        <p v-if="!tableNodes.length" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Este evento todavía no tiene mesas distribuidas.</p>
    </section>
</template>
