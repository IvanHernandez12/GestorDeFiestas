<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Muestra el detalle del evento y sus pestañas de gestión.
 */

import { computed, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';
import TableCanvas from '../../Components/TableCanvas.vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const tabs = ['Invitados', 'Salón', 'QR', 'Seguimiento', 'Evento', 'Info'];
const activeTab = ref('Salón');
const guests = ref([]);
const selectedGuest = ref(null);
const isLoadingGuests = ref(false);
const guestSuccess = ref('');
const guestError = ref('');
const photos = ref([]);
const guestForm = ref({ name: '', email: '', category_id: '' });
const photoInput = ref(null);

const guestCategories = computed(() => props.event.guest_categories ?? []);
const selectedGuestQrValue = computed(() => selectedGuest.value?.qr_token ?? '');
const providerList = computed(() => {
    const eventProviders = (props.event.packages ?? []).flatMap((packageOption) => packageOption.providers ?? []);

    if (eventProviders.length) {
        return eventProviders.map((provider) => ({
            id: provider.id,
            name: provider.name,
            category: provider.category,
            contact: provider.contact_info || 'Contacto pendiente',
        }));
    }

    return [
        { id: 'sound', name: 'Proveedor de sonido', category: 'Sonido', contact: 'Contacto pendiente' },
        { id: 'waiters', name: 'Servicio de camareros', category: 'Camareros', contact: 'Contacto pendiente' },
        { id: 'bakery', name: 'Proveedor de repostería', category: 'Repostería', contact: 'Contacto pendiente' },
    ];
});

function formatDate(value) {
    if (!value) {
        return 'Fecha pendiente';
    }

    return new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${String(value).slice(0, 10)}T12:00:00`));
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
}

function getXsrfToken() {
    const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return xsrfCookie ? decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) : '';
}

function getValidationMessage(responseData) {
    return Object.values(responseData.errors ?? {})[0]?.[0]
        ?? responseData.message
        ?? 'No fue posible completar la operación.';
}

async function loadGuests() {
    isLoadingGuests.value = true;
    guestError.value = '';

    try {
        const response = await fetch(`/api/events/${props.event.id}/guests`, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const responseData = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(getValidationMessage(responseData));
        }

        guests.value = responseData.guests ?? [];
        selectedGuest.value = guests.value[0] ?? null;
    } catch (error) {
        guestError.value = error.message || 'No fue posible cargar los invitados.';
    } finally {
        isLoadingGuests.value = false;
    }
}

async function addGuest() {
    guestSuccess.value = '';
    guestError.value = '';

    try {
        const response = await fetch(`/api/events/${props.event.id}/guests`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({
                name: guestForm.value.name.trim(),
                email: guestForm.value.email.trim() || null,
                category_id: guestForm.value.category_id,
            }),
        });
        const responseData = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(getValidationMessage(responseData));
        }

        const guest = responseData.guest;
        guests.value.unshift(guest);
        selectedGuest.value = guest;
        guestForm.value = { name: '', email: '', category_id: '' };
        guestSuccess.value = 'Invitado agregado correctamente.';
    } catch (error) {
        guestError.value = error.message || 'No fue posible agregar al invitado.';
    }
}

function showGuestQr(guest) {
    selectedGuest.value = guest;
}

onMounted(loadGuests);

function openPhotoPicker() {
    photoInput.value?.click();
}

function addPhotos(event) {
    const selectedFiles = Array.from(event.target.files ?? []);

    for (const file of selectedFiles) {
        photos.value.push({
            id: `${file.name}-${file.lastModified}`,
            name: file.name,
            url: URL.createObjectURL(file),
        });
    }

    event.target.value = '';
}
</script>

<template>
    <Head :title="`Detalles de ${event.name}`" />

    <AuthenticatedLayout>
        <div class="space-y-7">
            <Link href="/events" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
                <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M16 10H4m5 5-5-5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                Mis eventos
            </Link>

            <header class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ event.category }}</span>
                    <span class="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Planificando</span>
                </div>
                <div class="mt-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ event.name }}</h1>
                    <p class="text-sm font-semibold text-slate-500">Folio EVT-{{ String(event.id).padStart(6, '0') }}</p>
                </div>
            </header>

            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen del evento">
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Fecha</p>
                    <p class="mt-2 text-lg font-bold text-slate-900">{{ formatDate(event.event_date) }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Ubicación</p>
                    <p class="mt-2 truncate text-lg font-bold text-slate-900">{{ event.venue?.name ?? 'Salón por definir' }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Invitados</p>
                    <p class="mt-2 text-lg font-bold text-slate-900">{{ new Intl.NumberFormat('es-MX').format(event.guest_count ?? 0) }}</p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Presupuesto</p>
                    <p class="mt-2 text-lg font-bold text-slate-900">{{ formatCurrency(event.total_budget) }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 px-3 sm:px-5" role="tablist" aria-label="Secciones del evento">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab"
                        class="shrink-0 border-b-2 px-4 py-4 text-sm font-semibold transition"
                        :class="activeTab === tab ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="activeTab = tab"
                    >{{ tab }}</button>
                </nav>

                <div class="p-5 sm:p-7">
                    <section v-if="activeTab === 'Salón'" role="tabpanel" aria-label="Salón">
                        <TableCanvas :key="event.id" :event="event" :tables="event.tables ?? []" @close="activeTab = 'Info'" />
                    </section>

                    <section v-else-if="activeTab === 'Invitados'" class="space-y-6" role="tabpanel" aria-label="Invitados">
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">Lista de invitados</h2>
                            <p class="mt-1 text-sm text-slate-500">Agrega y organiza a las personas que asistirán.</p>
                        </div>
                        <p v-if="guestSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ guestSuccess }}</p>
                        <p v-if="guestError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">{{ guestError }}</p>
                        <p v-if="!guestCategories.length" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Agrega categorías de invitados a este evento antes de registrar invitados.</p>
                        <form class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[1fr_1fr_1fr_auto]" @submit.prevent="addGuest">
                            <label class="space-y-1.5 text-sm font-semibold text-slate-700">Nombre
                                <input v-model="guestForm.name" required type="text" placeholder="Nombre del invitado" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                            </label>
                            <label class="space-y-1.5 text-sm font-semibold text-slate-700">Correo
                                <input v-model="guestForm.email" type="email" placeholder="correo@ejemplo.com (opcional)" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                            </label>
                            <label class="space-y-1.5 text-sm font-semibold text-slate-700">Categoría
                                <select v-model="guestForm.category_id" required class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 font-normal outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                                    <option disabled value="">Selecciona una categoría</option>
                                    <option v-for="category in guestCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                </select>
                            </label>
                            <button type="submit" :disabled="!guestCategories.length || isLoadingGuests" class="self-end rounded-lg bg-blue-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-slate-300">Agregar</button>
                        </form>
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="w-full min-w-[600px] text-left text-sm">
                                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3 font-semibold">Nombre</th><th class="px-4 py-3 font-semibold">Correo</th><th class="px-4 py-3 font-semibold">Categoría</th><th class="px-4 py-3 font-semibold">QR</th></tr></thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="guest in guests" :key="guest.id"><td class="px-4 py-3 font-semibold text-slate-800">{{ guest.name }}</td><td class="px-4 py-3 text-slate-500">{{ guest.email || '—' }}</td><td class="px-4 py-3 text-slate-500">{{ guest.category?.name ?? '—' }}</td><td class="px-4 py-3"><button type="button" class="font-semibold text-blue-600 hover:text-blue-700" @click="showGuestQr(guest)">Ver QR</button></td></tr>
                                    <tr v-if="isLoadingGuests"><td colspan="4" class="px-4 py-10 text-center text-slate-500">Cargando invitados…</td></tr>
                                    <tr v-else-if="!guests.length"><td colspan="4" class="px-4 py-10 text-center text-slate-500">Aún no hay invitados agregados.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-if="selectedGuest" class="mx-auto flex max-w-2xl flex-col items-center gap-5 rounded-xl border border-slate-200 bg-slate-50 p-6 text-center sm:flex-row sm:text-left">
                            <div class="rounded-xl bg-white p-3 shadow-sm"><QrcodeVue :value="selectedGuestQrValue" :size="168" level="M" render-as="svg" /></div>
                            <div><p class="text-xs font-bold uppercase tracking-wide text-blue-600">QR del invitado</p><h3 class="mt-1 text-lg font-bold text-slate-950">{{ selectedGuest.name }}</h3><p class="mt-2 text-sm text-slate-500">La app Flutter puede leer este token para identificar al invitado.</p><p class="mt-2 break-all font-mono text-xs text-slate-500">{{ selectedGuest.qr_token }}</p></div>
                        </div>
                    </section>

                    <section v-else-if="activeTab === 'QR'" class="mx-auto flex max-w-xl flex-col items-center rounded-xl border border-slate-200 bg-slate-50 px-6 py-10 text-center" role="tabpanel" aria-label="Código QR">
                        <h2 class="text-xl font-bold text-slate-950">Código QR del evento</h2>
                        <template v-if="selectedGuest">
                            <p class="mt-2 text-sm text-slate-500">Código personal de {{ selectedGuest.name }}.</p>
                            <div class="mt-6 rounded-xl bg-white p-4 shadow-sm"><QrcodeVue :value="selectedGuestQrValue" :size="240" level="M" render-as="svg" /></div>
                            <p class="mt-4 break-all font-mono text-xs text-slate-500">{{ selectedGuest.qr_token }}</p>
                        </template>
                        <p v-else class="mt-6 rounded-xl bg-white px-5 py-8 text-sm text-slate-500">Agrega un invitado y selecciona “Ver QR” para mostrar su código personal.</p>
                    </section>

                    <section v-else-if="activeTab === 'Seguimiento'" class="space-y-5" role="tabpanel" aria-label="Seguimiento de proveedores">
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">Seguimiento de proveedores</h2>
                            <p class="mt-1 text-sm text-slate-500">Consulta fechas de entrega y datos de contacto.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <article v-for="provider in providerList" :key="provider.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div><p class="text-xs font-bold uppercase tracking-wide text-blue-600">{{ provider.category }}</p><h3 class="mt-1 font-bold text-slate-900">{{ provider.name }}</h3></div>
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">Pendiente</span>
                                </div>
                                <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm">
                                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Fecha de entrega</dt><dd class="font-semibold text-slate-700">Por confirmar</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Contacto</dt><dd class="truncate font-semibold text-slate-700">{{ provider.contact }}</dd></div>
                                </dl>
                            </article>
                        </div>
                    </section>

                    <section v-else-if="activeTab === 'Evento'" class="space-y-5" role="tabpanel" aria-label="Álbum del evento">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div><h2 class="text-xl font-bold text-slate-950">Álbum de fotos</h2><p class="mt-1 text-sm text-slate-500">Guarda los mejores momentos del evento.</p></div>
                            <input ref="photoInput" type="file" accept="image/*" capture="environment" multiple class="hidden" @change="addPhotos">
                            <button type="button" class="rounded-lg bg-blue-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-600" @click="openPhotoPicker">Tomar foto</button>
                        </div>
                        <div v-if="photos.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <figure v-for="photo in photos" :key="photo.id" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50"><img :src="photo.url" :alt="photo.name" class="aspect-[4/3] w-full object-cover"><figcaption class="truncate px-3 py-2 text-sm text-slate-500">{{ photo.name }}</figcaption></figure>
                        </div>
                        <div v-else class="grid min-h-64 place-items-center rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center">
                            <div><span class="mx-auto grid size-12 place-items-center rounded-xl bg-white text-slate-400 shadow-sm"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2.5" /><circle cx="8.5" cy="9" r="1.5" /><path d="m21 15-5-5L5 20" /></svg></span><p class="mt-3 font-semibold text-slate-700">Aún no hay fotos</p><p class="mt-1 text-sm text-slate-500">Usa “Tomar foto” para comenzar el álbum.</p></div>
                        </div>
                    </section>

                    <section v-else class="space-y-5" role="tabpanel" aria-label="Información del evento">
                        <div><h2 class="text-xl font-bold text-slate-950">Información del evento</h2><p class="mt-1 text-sm text-slate-500">Detalles y preferencias de organización.</p></div>
                        <dl class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Categoría</dt><dd class="mt-1 font-semibold text-slate-900">{{ event.category }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Salón</dt><dd class="mt-1 font-semibold text-slate-900">{{ event.venue?.name ?? 'Por seleccionar' }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Dirección</dt><dd class="mt-1 font-semibold text-slate-900">{{ event.venue?.address ?? 'Pendiente' }}</dd></div>
                            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Identificador público</dt><dd class="mt-1 break-all font-semibold text-slate-900">{{ event.public_uuid }}</dd></div>
                        </dl>
                        <div class="rounded-xl border border-slate-200 p-4"><h3 class="text-sm font-semibold text-slate-700">Reglamento</h3><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-500">{{ event.rules || 'No se ha agregado un reglamento.' }}</p></div>
                    </section>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
