<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Muestra el dashboard con los eventos del organizador.
 */

import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    events: {
        type: Array,
        default: () => [],
    },
});
const deletingEventId = ref(null);

function formatDate(value) {
    if (!value) {
        return 'Fecha pendiente';
    }

    const date = new Date(`${String(value).slice(0, 10)}T12:00:00`);

    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
}

function deleteEvent(event) {
    if (deletingEventId.value !== null || !window.confirm(`¿Seguro que deseas eliminar el evento "${event.name}"?`)) {
        return;
    }

    deletingEventId.value = event.id;
    router.delete(`/events/${event.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingEventId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Mis eventos" />

    <AuthenticatedLayout>
        <section class="space-y-9">
            <div class="flex flex-col justify-between gap-7 rounded-3xl border border-blue-100 bg-white px-6 py-8 shadow-sm sm:px-10 sm:py-10 lg:flex-row lg:items-end">
                <div class="max-w-2xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.4" /><path d="M6.5 2.5v3M13.5 2.5v3M3 8h14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" /></svg>
                        Gestión de eventos
                    </span>
                    <h1 class="mt-5 max-w-2xl text-4xl font-bold leading-[1.08] tracking-tight text-slate-950 sm:text-5xl">
                        Organiza tu evento <span class="text-blue-500">de principio a fin.</span>
                    </h1>
                    <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">Crea el evento, recibe recomendaciones de proveedores, gestiona invitados y comparte invitaciones con QR.</p>
                </div>
                <Link href="/events/create" class="inline-flex min-h-12 shrink-0 items-center justify-center gap-3 self-start rounded-xl bg-blue-500 px-6 py-3 text-sm font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-200 lg:self-auto">
                    <span class="grid size-7 place-items-center rounded-lg bg-white/15 text-xl leading-none">+</span>
                    Iniciar evento
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                </Link>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-bold tracking-tight text-slate-950">Tus eventos</h2>
                <p class="text-sm text-slate-500">{{ events.length }} {{ events.length === 1 ? 'evento' : 'eventos' }}</p>
            </div>

            <div v-if="events.length" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <article v-for="event in events" :key="event.id" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60 transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg hover:shadow-blue-950/5">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">{{ event.category }}</span>
                            <span class="rounded-full border border-blue-100 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-600">Planificando</span>
                        </div>
                        <h3 class="mt-4 line-clamp-2 min-h-14 text-xl font-bold leading-7 tracking-tight text-slate-950">{{ event.name }}</h3>

                        <div class="mt-5 space-y-3 border-t border-slate-100 pt-5 text-sm">
                            <div class="flex items-center gap-3 text-slate-600">
                                <svg class="size-4 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><rect x="3" y="4" width="14" height="13" rx="2.5" stroke="currentColor" stroke-width="1.5" /><path d="M6.5 2.5v3M13.5 2.5v3M3 8h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                                <span>{{ formatDate(event.event_date) }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-slate-600">
                                <svg class="size-4 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M16 8.25c0 4.2-6 9-6 9s-6-4.8-6-9a6 6 0 1 1 12 0Z" stroke="currentColor" stroke-width="1.5" /><circle cx="10" cy="8" r="2" stroke="currentColor" stroke-width="1.5" /></svg>
                                <span class="truncate">{{ event.venue?.name ?? 'Salón por definir' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 text-slate-600">
                                <span class="inline-flex items-center gap-3">
                                    <svg class="size-4 shrink-0 text-blue-500" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="7.25" cy="6.5" r="2.75" stroke="currentColor" stroke-width="1.5" /><path d="M2.5 16v-.75a4.75 4.75 0 0 1 9.5 0V16m1.25-9.75a2.75 2.75 0 0 1 0 5.25M15 13a4.75 4.75 0 0 1 2.5 4.25V18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                                    {{ new Intl.NumberFormat('es-MX').format(event.guest_count ?? 0) }} invitados
                                </span>
                                <span class="font-semibold text-slate-800">{{ formatCurrency(event.total_budget) }}</span>
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <span class="text-xs font-medium text-slate-500">Folio EVT-{{ String(event.id).padStart(6, '0') }}</span>
                            <div class="flex items-center gap-3">
                                <button type="button" :disabled="deletingEventId !== null" class="grid size-9 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 disabled:cursor-wait disabled:opacity-50" :aria-label="`Eliminar ${event.name}`" title="Eliminar evento" @click="deleteEvent(event)">
                                    <svg v-if="deletingEventId === event.id" class="size-4 animate-spin" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-opacity=".3" stroke-width="2" /><path d="M17 10a7 7 0 0 0-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                                    <svg v-else class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 6h12m-10 0 .7 10h6.6L14 6M8 6V4h4v2m-3 3v4m2-4v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </button>
                                <Link :href="`/events/${event.id}`" class="inline-flex items-center gap-1.5 text-sm font-bold text-slate-900 transition hover:text-blue-600">
                                    Ver detalle
                                    <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm sm:px-12">
                <span class="mx-auto grid size-16 place-items-center rounded-3xl bg-blue-50 text-blue-600">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3.5" y="4.5" width="17" height="16" rx="3" /><path d="M8 2.75v3.5M16 2.75v3.5M3.5 9.5h17m-9 3v5m-2.5-2.5H14" /></svg>
                </span>
                <h3 class="mt-5 text-xl font-bold text-slate-950">Tu próximo gran evento comienza aquí</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Crea tu primer evento para organizar el salón, los invitados y el presupuesto desde un solo lugar.</p>
                <Link href="/events/create" class="mt-6 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-blue-500 px-6 py-3 text-sm font-bold text-white shadow-md shadow-blue-500/20 transition hover:bg-blue-600">
                    <span class="text-lg leading-none">+</span> Iniciar evento
                </Link>
            </div>
        </section>

    </AuthenticatedLayout>
</template>
