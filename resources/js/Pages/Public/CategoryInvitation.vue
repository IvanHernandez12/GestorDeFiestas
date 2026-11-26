<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 26/noviembre/2026
 * @modificado 26/noviembre/2026
 * @descripción Permite responder la invitación grupal de una categoría y muestra el avance de respuestas.
 */

import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    publicUuid: { type: String, required: true },
});

const invitation = ref(null);
const isLoading = ref(true);
const errorMessage = ref('');
const respondingGuestId = ref(null);
const responseSuccess = ref('');
const responseError = ref('');

const eventDetails = computed(() => invitation.value?.event ?? null);
const categoryDetails = computed(() => invitation.value?.category ?? null);
const venueDetails = computed(() => invitation.value?.venue ?? null);
const guests = computed(() => invitation.value?.guests ?? []);
const statusCounts = computed(() => invitation.value?.status_counts ?? {});
const statusLabels = computed(() => invitation.value?.status_labels ?? {});

function formatDate(value) {
    if (!value) {
        return 'Fecha por definir';
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${value}T12:00:00`));
}

function getXsrfToken() {
    const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return xsrfCookie ? decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) : '';
}

async function loadInvitation() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch(`/api/public/categories/${props.publicUuid}`, {
            headers: { Accept: 'application/json' },
        });
        const responseData = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(responseData.message ?? 'No fue posible cargar la invitación.');
        }

        invitation.value = responseData;
    } catch (error) {
        errorMessage.value = error.message || 'No fue posible cargar la invitación.';
    } finally {
        isLoading.value = false;
    }
}

async function respond(guest, status) {
    if (respondingGuestId.value !== null) {
        return;
    }

    respondingGuestId.value = guest.id;
    responseSuccess.value = '';
    responseError.value = '';

    try {
        const response = await fetch(`/api/public/categories/${props.publicUuid}/responses`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({ guest_id: guest.id, status }),
        });
        const responseData = await response.json().catch(() => ({}));

        if (!response.ok) {
            const validationMessage = Object.values(responseData.errors ?? {})[0]?.[0];

            throw new Error(validationMessage ?? responseData.message ?? 'No fue posible registrar la respuesta.');
        }

        invitation.value = responseData;
        responseSuccess.value = `${guest.name}: ${responseData.guest.status_label}.`;
    } catch (error) {
        responseError.value = error.message || 'No fue posible registrar la respuesta.';
    } finally {
        respondingGuestId.value = null;
    }
}

onMounted(loadInvitation);
</script>

<template>
    <Head :title="`Invitación ${categoryDetails?.name ?? ''}`.trim()" />

    <main class="min-h-screen bg-slate-100 px-4 py-10 text-slate-900">
        <section class="mx-auto max-w-4xl space-y-6">
            <header class="rounded-3xl bg-blue-600 px-6 py-10 text-white shadow-lg shadow-blue-600/20 sm:px-10">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Invitación grupal</p>
                <h1 class="mt-3 text-3xl font-bold sm:text-4xl">{{ eventDetails?.name ?? 'Cargando evento…' }}</h1>
                <p v-if="categoryDetails" class="mt-3 text-blue-100">Categoría {{ categoryDetails.name }} · {{ categoryDetails.total }} invitados</p>
            </header>

            <p v-if="isLoading" class="rounded-xl border border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-500">Cargando invitación…</p>
            <p v-else-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-8 text-center text-sm font-semibold text-rose-700" role="alert">{{ errorMessage }}</p>

            <template v-else-if="eventDetails">
                <dl class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Fecha</dt>
                        <dd class="mt-2 font-bold">{{ formatDate(eventDetails.date) }}</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Lugar</dt>
                        <dd class="mt-2 font-bold">{{ venueDetails?.name ?? 'Por definir' }}</dd>
                        <dd class="mt-1 text-xs text-slate-500">{{ venueDetails?.address ?? 'Dirección por definir' }}</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Avance</dt>
                        <dd class="mt-2 text-sm font-semibold text-slate-700">
                            {{ statusCounts.confirmed ?? 0 }} confirmados · {{ statusCounts.rejected ?? 0 }} rechazados · {{ statusCounts.pending ?? 0 }} pendientes
                        </dd>
                    </div>
                </dl>

                <div v-if="eventDetails.rules" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Reglamento</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ eventDetails.rules }}</p>
                </div>

                <p v-if="responseSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800" role="status">{{ responseSuccess }}</p>
                <p v-if="responseError" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-3 text-sm font-semibold text-rose-700" role="alert">{{ responseError }}</p>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Invitado</th>
                                <th class="px-4 py-3 font-semibold">Estatus</th>
                                <th class="px-4 py-3 font-semibold">Respuesta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="guest in guests" :key="guest.id">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ guest.name }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="guest.status === 'confirmed' ? 'bg-emerald-50 text-emerald-700' : guest.status === 'rejected' ? 'bg-rose-50 text-rose-700' : 'bg-amber-50 text-amber-700'">
                                        {{ statusLabels[guest.status] ?? guest.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" :disabled="respondingGuestId !== null" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" @click="respond(guest, 'confirmed')">Confirmo asistencia</button>
                                        <button type="button" :disabled="respondingGuestId !== null" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700 transition hover:bg-rose-50 disabled:cursor-not-allowed disabled:border-slate-200 disabled:text-slate-400" @click="respond(guest, 'rejected')">Rechazo invitación</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!guests.length">
                                <td colspan="3" class="px-4 py-10 text-center text-slate-500">Esta categoría aún no tiene invitados registrados.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </section>
    </main>
</template>
