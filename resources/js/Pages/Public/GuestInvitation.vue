<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 25/noviembre/2026
 * @modificado 25/noviembre/2026
 * @descripción Muestra públicamente los datos del evento a partir del token QR del invitado.
 */

import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    qrToken: { type: String, required: true },
});

const invitation = ref(null);
const isLoading = ref(true);
const errorMessage = ref('');

const eventDetails = computed(() => invitation.value?.event ?? null);
const venueDetails = computed(() => invitation.value?.venue ?? null);
const guestDetails = computed(() => invitation.value?.guest ?? null);

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

async function loadInvitation() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch(`/api/public/guest/${props.qrToken}`, {
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

onMounted(loadInvitation);
</script>

<template>
    <Head title="Invitación" />

    <main class="min-h-screen bg-slate-100 px-4 py-10 text-slate-900">
        <section class="mx-auto max-w-3xl space-y-6">
            <header class="rounded-3xl bg-blue-600 px-6 py-10 text-white shadow-lg shadow-blue-600/20 sm:px-10">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Invitación personal</p>
                <h1 class="mt-3 text-3xl font-bold sm:text-4xl">{{ eventDetails?.name ?? 'Cargando evento…' }}</h1>
                <p v-if="guestDetails" class="mt-3 text-blue-100">Para {{ guestDetails.name }}</p>
            </header>

            <p v-if="isLoading" class="rounded-xl border border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-500">Cargando invitación…</p>
            <p v-else-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-8 text-center text-sm font-semibold text-rose-700" role="alert">{{ errorMessage }}</p>

            <template v-else-if="eventDetails">
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Fecha</dt>
                        <dd class="mt-2 text-lg font-bold">{{ formatDate(eventDetails.date) }}</dd>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Salón</dt>
                        <dd class="mt-2 text-lg font-bold">{{ venueDetails?.name ?? 'Por definir' }}</dd>
                        <dd class="mt-1 text-sm text-slate-500">{{ venueDetails?.address ?? 'Dirección por definir' }}</dd>
                    </div>
                </dl>

                <div v-if="eventDetails.rules" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-slate-500">Reglamento</h2>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ eventDetails.rules }}</p>
                </div>

                <div v-if="invitation.reservation" class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                    <h2 class="font-bold">Tu lugar</h2>
                    <p class="mt-1">Mesa {{ invitation.reservation.table_number }} · Silla {{ invitation.reservation.seat_number }}</p>
                </div>
            </template>
        </section>
    </main>
</template>
