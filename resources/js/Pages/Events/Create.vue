<script>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Presenta el formulario para crear un evento y buscar un salón.
 */

let mapsLibraryPromise;
</script>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { importLibrary, setOptions } from '@googlemaps/js-api-loader';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const form = reactive({
    name: '',
    category: '',
    event_date: '',
    guest_count: '',
    total_budget: '',
    rules: '',
    venue_id: '',
});

const guestCategories = ref([
    { name: 'Categoría 1', guest_count: 0, priority: 1 },
    { name: 'Categoría 2', guest_count: 0, priority: 2 },
]);
const venueSearch = ref('');
const venues = ref([]);
const selectedVenue = ref(null);
const isSearching = ref(false);
const isSubmitting = ref(false);
const hasCreatedEvent = ref(false);
const errors = ref({});
const errorMessage = ref('');
const searchError = ref('');
const mapElement = ref(null);
const mapInstance = ref(null);
const mapError = ref('');
let mapMarker;
let searchTimer;
let searchController;

const tolucaCenter = { lat: 19.2826, lng: -99.6557 };
const assignedGuestCount = computed(() => guestCategories.value.reduce((total, category) => total + Number(category.guest_count || 0), 0));
const remainingGuestCount = computed(() => Math.max(0, Number(form.guest_count || 0) - assignedGuestCount.value));
const categoriesFitGuestCount = computed(() => Number(form.guest_count) > 0 && assignedGuestCount.value <= Number(form.guest_count));
const minimumEventDate = (() => {
    const date = new Date();
    date.setDate(date.getDate() + 15);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
})();

function addGuestCategory() {
    guestCategories.value.push({
        name: `Categoría ${guestCategories.value.length + 1}`,
        guest_count: 0,
        priority: guestCategories.value.length + 1,
    });
}

function removeGuestCategory(index) {
    if (index > 1) {
        guestCategories.value.splice(index, 1);

        guestCategories.value.forEach((category, categoryIndex) => {
            category.name = `Categoría ${categoryIndex + 1}`;
        });
    }
}

function getXsrfToken() {
    const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return xsrfCookie ? decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) : '';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
}

async function searchVenues() {
    searchController?.abort();
    searchController = new AbortController();
    const params = new URLSearchParams();

    if (Number(form.guest_count) > 0) {
        params.set('capacity', String(form.guest_count));
    }

    if (Number(form.total_budget) > 0) {
        params.set('max_price', String(form.total_budget));
    }

    if (venueSearch.value.trim()) {
        params.set('name', venueSearch.value.trim());
    }

    isSearching.value = true;
    searchError.value = '';

    try {
        const query = params.toString();
        const response = await fetch(`/api/venues/search${query ? `?${query}` : ''}`, {
            headers: { Accept: 'application/json' },
            signal: searchController.signal,
        });

        if (!response.ok) {
            throw new Error('No fue posible cargar los salones.');
        }

        venues.value = await response.json();
    } catch (error) {
        if (error.name !== 'AbortError') {
            searchError.value = error.message || 'No fue posible cargar los salones.';
        }
    } finally {
        isSearching.value = false;
    }
}

function selectVenue(venue) {
    selectedVenue.value = venue;
    form.venue_id = venue.id;
    delete errors.value.venue_id;

    const venuePosition = {
        lat: Number(venue.lat),
        lng: Number(venue.lng),
    };

    mapInstance.value?.panTo(venuePosition);
    mapInstance.value?.setZoom(15);

    if (mapMarker) {
        mapMarker.setPosition(venuePosition);
    } else if (mapInstance.value) {
        mapMarker = new google.maps.Marker({
            map: mapInstance.value,
            position: venuePosition,
            title: venue.name,
        });
    }
}

async function initializeMap() {
    const apiKey = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;

    if (!apiKey) {
        mapError.value = 'No se encontró VITE_GOOGLE_MAPS_API_KEY en la configuración de Vite.';

        return;
    }

    try {
        if (!mapsLibraryPromise) {
            setOptions({ key: apiKey, v: 'weekly' });
            mapsLibraryPromise = importLibrary('maps');
        }

        const { Map } = await mapsLibraryPromise;

        mapInstance.value = new Map(mapElement.value, {
            center: tolucaCenter,
            zoom: 12,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
        });

        if (selectedVenue.value) {
            selectVenue(selectedVenue.value);
        }
    } catch {
        mapError.value = 'No fue posible cargar Google Maps. Verifica la clave y su configuración.';
    }
}

async function submitEvent() {
    if (isSubmitting.value || hasCreatedEvent.value) {
        return;
    }

    isSubmitting.value = true;
    errors.value = {};
    errorMessage.value = '';

    try {
        const response = await fetch('/api/events', {
            method: 'POST',
            credentials: 'include',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({
                name: form.name.trim(),
                category: form.category,
                event_date: form.event_date,
                guest_count: Number(form.guest_count),
                total_budget: Number(form.total_budget),
                rules: form.rules.trim() || null,
                venue_id: Number(form.venue_id),
                guest_categories: guestCategories.value.map((category) => ({
                    name: category.name.trim(),
                    guest_count: Number(category.guest_count || 0),
                    priority: Number(category.priority),
                })),
            }),
        });
        const responseData = await response.json().catch(() => ({}));

        if (response.status === 401) {
            router.visit('/login');

            return;
        }

        if (response.status === 422) {
            errors.value = responseData.errors ?? {};
            errorMessage.value = 'Revisa los datos marcados antes de continuar.';

            return;
        }

        if (!response.ok) {
            errorMessage.value = responseData.message ?? 'No fue posible crear el evento.';

            return;
        }

        hasCreatedEvent.value = true;

        if (!Number.isInteger(responseData.id)) {
            errorMessage.value = 'El evento se creó, pero no recibimos su identificador. Revisa Mis eventos en lugar de volver a enviar el formulario.';

            return;
        }

        router.visit(`/events/${responseData.id}/packages`, {
            onError: () => {
                errorMessage.value = 'El evento se guardó, pero no fue posible abrir la selección de paquetes.';
            },
        });
    } catch {
        errorMessage.value = 'Ocurrió un problema de conexión. Intenta de nuevo.';
    } finally {
        isSubmitting.value = false;
    }
}

watch([venueSearch, () => form.guest_count, () => form.total_budget], () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(searchVenues, 300);
}, { immediate: true });

watch(() => form.guest_count, (guestCount) => {
    if (selectedVenue.value && Number(guestCount) > selectedVenue.value.capacity) {
        selectedVenue.value = null;
        form.venue_id = '';
    }
});

onBeforeUnmount(() => {
    window.clearTimeout(searchTimer);
    searchController?.abort();
    mapMarker?.setMap(null);
    mapMarker = null;
    mapInstance.value = null;
});

onMounted(initializeMap);
</script>

<template>
    <Head title="Iniciar evento" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl space-y-7">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <Link href="/events" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M16 10H4m5 5-5-5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Volver a mis eventos
                    </Link>
                    <p class="mt-6 text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Nuevo evento</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Vamos a planear algo increíble.</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Cuéntanos lo esencial y encuentra un salón que se ajuste a tu celebración.</p>
                </div>
                <div class="hidden size-16 place-items-center rounded-3xl bg-blue-50 text-blue-600 sm:grid">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m12 3 1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8L12 3Z" /><path d="m19 15 .9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15Z" /></svg>
                </div>
            </div>

            <div v-if="errorMessage" class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-800" role="alert">
                <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.5" /><path d="M10 6.5v4m0 3h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                {{ errorMessage }}
            </div>

            <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(330px,0.9fr)]" @submit.prevent="submitEvent">
                <div class="flex flex-col gap-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-7">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-sm font-bold text-blue-700">01</span>
                        <div><h2 class="font-bold text-slate-950">Datos del evento</h2><p class="mt-0.5 text-xs text-slate-500">Completa la información para empezar.</p></div>
                    </div>

                    <div class="mt-6 space-y-5">
                        <div>
                            <label for="event-name" class="mb-2 block text-sm font-semibold text-slate-700">Nombre del evento</label>
                            <input id="event-name" v-model="form.name" type="text" maxlength="255" required placeholder="Ej. Boda de Ana y Luis" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                            <p v-if="errors.name" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.name[0] }}</p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="event-category" class="mb-2 block text-sm font-semibold text-slate-700">Categoría</label>
                                <select id="event-category" v-model="form.category" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                                    <option disabled value="">Selecciona una categoría</option>
                                    <option>Boda</option>
                                    <option>Cumpleaños</option>
                                    <option>XV años</option>
                                    <option>Graduación</option>
                                    <option>Evento corporativo</option>
                                    <option>Otro</option>
                                </select>
                                <p v-if="errors.category" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.category[0] }}</p>
                            </div>
                            <div>
                                <label for="event-date" class="mb-2 block text-sm font-semibold text-slate-700">Fecha</label>
                                <input id="event-date" v-model="form.event_date" type="date" :min="minimumEventDate" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                                <p v-if="errors.event_date" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.event_date[0] }}</p>
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="guest-count" class="mb-2 block text-sm font-semibold text-slate-700">Cantidad de invitados</label>
                                <div class="relative">
                                    <input id="guest-count" v-model="form.guest_count" type="number" min="1" max="100000" step="1" required placeholder="50" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-16 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                                    <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-medium text-slate-400">personas</span>
                                </div>
                                <p v-if="errors.guest_count" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.guest_count[0] }}</p>
                            </div>
                            <div>
                                <label for="event-budget" class="mb-2 block text-sm font-semibold text-slate-700">Presupuesto total</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-sm font-semibold text-slate-400">$</span>
                                    <input id="event-budget" v-model="form.total_budget" type="number" min="5000" max="100000000" step="100" required placeholder="50,000" class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-9 pr-14 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                                    <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-medium text-slate-400">MXN</span>
                                </div>
                                <p v-if="errors.total_budget" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.total_budget[0] }}</p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                            <div class="flex items-start gap-3">
                                <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white text-blue-600 shadow-sm">
                                    <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7.5" stroke="currentColor" stroke-width="1.5" /><path d="M10 9v4m0-6h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                                </span>
                                <p class="text-xs leading-5 text-blue-900">El presupuesto restante después del salón te ayudará a elegir paquetes de proveedores.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-7">
                    <div class="flex items-center gap-3 border-b border-slate-100 pb-5">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-sm font-bold text-blue-700">03</span>
                        <div>
                            <h2 class="font-bold text-slate-950">Reglamento del evento</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Comparte las reglas importantes con tus invitados.</p>
                        </div>
                    </div>
                    <label for="event-rules" class="mb-2 mt-5 block text-sm font-semibold text-slate-700">Reglas e indicaciones</label>
                    <textarea id="event-rules" v-model="form.rules" rows="5" maxlength="5000" placeholder="Ej. No se admiten niños, código de vestimenta formal…" class="w-full resize-y rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50"></textarea>
                    <p v-if="errors.rules" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.rules[0] }}</p>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-7">
                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 pb-5">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-sm font-bold text-blue-700">04</span>
                            <div>
                                <h2 class="font-bold text-slate-950">Prioridades y distribución</h2>
                                <p class="mt-0.5 text-xs text-slate-500">Máximo 8 personas por mesa.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div v-for="(category, index) in guestCategories" :key="category.name" class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-3">
                            <span class="size-2.5 shrink-0 rounded-full" :class="index === 0 ? 'bg-blue-500' : 'bg-blue-300'"></span>
                            <label :for="`guest-category-${index}`" class="min-w-24 flex-1 text-sm font-semibold text-slate-700">{{ category.name }}</label>
                            <input :id="`guest-category-${index}`" v-model.number="category.guest_count" type="number" min="0" step="1" :max="Math.min(Number(form.guest_count) || 100000, 100000)" class="w-24 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-50" :aria-label="`Invitados en ${category.name}`">
                            <span class="hidden text-xs text-slate-500 sm:inline">invitados</span>
                            <button v-if="index > 1" type="button" class="grid size-8 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600" :aria-label="`Eliminar ${category.name}`" @click="removeGuestCategory(index)">×</button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                        <button type="button" class="inline-flex items-center gap-2 rounded-lg px-2 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-50" @click="addGuestCategory">
                            <span class="grid size-5 place-items-center rounded-full border border-blue-200 text-base leading-none">+</span>
                            Agregar categoría
                        </button>
                        <span class="text-xs font-semibold" :class="categoriesFitGuestCount ? 'text-emerald-700' : 'text-rose-600'">
                            {{ assignedGuestCount }} de {{ Number(form.guest_count || 0) }} asignados
                        </span>
                    </div>
                    <p v-if="errors.guest_categories" class="mt-2 text-xs font-medium text-rose-600">{{ errors.guest_categories[0] }}</p>
                    <template v-for="(message, field) in errors" :key="field">
                        <p v-if="field.startsWith('guest_categories.') && field !== 'guest_categories'" class="mt-2 text-xs font-medium text-rose-600">
                            {{ message[0] }}
                        </p>
                    </template>
                    <p v-if="!errors.guest_categories && Number(form.guest_count) > 0 && !categoriesFitGuestCount" class="mt-2 text-xs text-rose-600">La suma de las categorías no puede exceder el total de invitados.</p>
                    <p v-if="!errors.guest_categories && categoriesFitGuestCount && remainingGuestCount > 0" class="mt-2 text-xs text-slate-500">{{ remainingGuestCount }} invitados restantes se agregarán a Categoría General.</p>
                </section>
                </div>

                <section class="space-y-5">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
                        <div class="flex items-center gap-3">
                            <span class="grid size-10 place-items-center rounded-xl bg-violet-50 text-sm font-bold text-violet-700">02</span>
                            <div><h2 class="font-bold text-slate-950">Encuentra tu salón</h2><p class="mt-0.5 text-xs text-slate-500">Busca una opción para tu celebración.</p></div>
                        </div>

                        <label for="venue-search" class="sr-only">Buscar salones por nombre</label>
                        <div class="relative mt-5">
                            <svg class="absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="8.75" cy="8.75" r="5.75" stroke="currentColor" stroke-width="1.5" /><path d="m13 13 4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" /></svg>
                            <input id="venue-search" v-model="venueSearch" type="search" placeholder="Buscar por nombre de salón" class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:bg-white focus:ring-4 focus:ring-blue-50">
                        </div>

                        <div class="relative mt-4 h-64 overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                            <div ref="mapElement" class="h-full w-full" aria-label="Mapa de salones en Toluca"></div>
                            <div v-if="mapError" class="absolute inset-0 grid place-items-center bg-slate-100/95 p-5 text-center text-sm text-rose-700" role="alert">
                                {{ mapError }}
                            </div>
                        </div>

                        <div class="mt-5 flex items-center justify-between gap-3">
                            <h3 class="text-sm font-bold text-slate-900">Salones disponibles</h3>
                            <span class="text-xs text-slate-400">{{ isSearching ? 'Buscando…' : `${venues.length} resultados` }}</span>
                        </div>
                        <p v-if="searchError" class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ searchError }}</p>

                        <div v-else-if="venues.length" class="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                            <button v-for="venue in venues" :key="venue.id" type="button" class="w-full rounded-2xl border p-4 text-left transition" :class="form.venue_id === venue.id ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100' : 'border-slate-200 hover:border-blue-200 hover:bg-slate-50'" @click="selectVenue(venue)">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-900">{{ venue.name }}</p>
                                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ venue.address }}</p>
                                    </div>
                                    <span class="grid size-5 shrink-0 place-items-center rounded-full border" :class="form.venue_id === venue.id ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white'">
                                        <svg v-if="form.venue_id === venue.id" class="size-3" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3 8 3 3 7-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    </span>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-semibold text-slate-500">
                                    <span>{{ new Intl.NumberFormat('es-MX').format(venue.capacity) }} personas</span>
                                    <span>{{ formatCurrency(venue.min_price) }} – {{ formatCurrency(venue.max_price) }}</span>
                                </div>
                            </button>
                        </div>
                        <p v-else class="mt-3 rounded-2xl bg-slate-50 px-4 py-5 text-center text-xs leading-5 text-slate-500">No encontramos salones con esos criterios. Prueba ajustar la capacidad, el presupuesto o el nombre.</p>

                        <p v-if="errors.venue_id" class="mt-3 text-xs font-medium text-rose-600">{{ errors.venue_id[0] }}</p>
                        <div v-if="selectedVenue" class="mt-4 rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-blue-700">Salón seleccionado</p>
                            <p class="mt-1 text-sm font-bold text-slate-900">{{ selectedVenue.name }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-600">{{ selectedVenue.address }}</p>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Resumen</p><p class="mt-1 font-bold text-slate-950">Tu evento</p></div>
                            <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600">
                                <svg class="size-5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m4 10 4 4 8-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </span>
                        </div>
                        <div class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm">
                            <div class="flex justify-between gap-3"><span class="text-slate-500">Invitados</span><span class="font-semibold text-slate-800">{{ form.guest_count || '—' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-slate-500">Presupuesto</span><span class="font-semibold text-slate-800">{{ form.total_budget ? formatCurrency(form.total_budget) : '—' }}</span></div>
                            <div class="flex justify-between gap-3"><span class="text-slate-500">Salón</span><span class="max-w-40 truncate text-right font-semibold text-slate-800">{{ selectedVenue?.name ?? 'Por seleccionar' }}</span></div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Link href="/events" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Cancelar</Link>
                        <button type="submit" :disabled="isSubmitting || hasCreatedEvent || !selectedVenue || !categoriesFitGuestCount" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-blue-500 px-7 py-3 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                            <svg v-if="isSubmitting" class="size-4 animate-spin" viewBox="0 0 20 20" fill="none" aria-hidden="true"><circle cx="10" cy="10" r="7" stroke="currentColor" stroke-opacity=".3" stroke-width="2" /><path d="M17 10a7 7 0 0 0-7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                            {{ isSubmitting ? 'Creando evento…' : 'Crear evento' }}
                        </button>
                    </div>
                </section>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
