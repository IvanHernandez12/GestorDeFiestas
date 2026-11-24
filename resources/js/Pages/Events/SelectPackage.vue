<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Permite revisar y confirmar paquetes recomendados de proveedores.
 */

import { onMounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const packages = ref([]);
const availableBudget = ref(0);
const isLoading = ref(true);
const confirmingPackageId = ref(null);
const errorMessage = ref('');

function getXsrfToken() {
    const xsrfCookie = document.cookie.split('; ').find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    return xsrfCookie ? decodeURIComponent(xsrfCookie.slice('XSRF-TOKEN='.length)) : '';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function providerPrice(provider) {
    return Number(provider.pivot?.price ?? 0);
}

function packageTotal(packageOption) {
    const totalInCents = packageOption.providers
        .filter((provider) => packageOption.selectedProviderIds.includes(provider.id))
        .reduce((total, provider) => total + Math.round(providerPrice(provider) * 100), 0);

    return totalInCents / 100;
}

function toggleProvider(packageOption, providerId) {
    const selectedProviderIds = packageOption.selectedProviderIds;
    const selectedIndex = selectedProviderIds.indexOf(providerId);

    if (selectedIndex === -1) {
        selectedProviderIds.push(providerId);
    } else {
        selectedProviderIds.splice(selectedIndex, 1);
    }
}

async function loadPackages() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch(`/api/events/${props.event.id}/packages`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (response.status === 401) {
            router.visit('/login');

            return;
        }

        if (!response.ok) {
            throw new Error('No fue posible cargar las recomendaciones.');
        }

        const responseData = await response.json();
        availableBudget.value = Number(responseData.available_budget ?? 0);
        packages.value = (responseData.packages ?? []).map((packageOption) => ({
            ...packageOption,
            selectedProviderIds: packageOption.providers.map((provider) => provider.id),
        }));
    } catch (error) {
        errorMessage.value = error.message || 'Ocurrió un problema de conexión.';
    } finally {
        isLoading.value = false;
    }
}

async function confirmPackage(packageOption) {
    confirmingPackageId.value = packageOption.id;
    errorMessage.value = '';

    try {
        const response = await fetch('/api/payments/create-preference', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body: JSON.stringify({
                event_id: props.event.id,
                package_id: packageOption.id,
                provider_ids: packageOption.selectedProviderIds,
            }),
        });

        const responseData = await response.json().catch(() => ({}));

        if (response.status === 401) {
            router.visit('/login');

            return;
        }

        if (response.status === 422) {
            errorMessage.value = Object.values(responseData.errors ?? {})[0]?.[0]
                ?? responseData.message
                ?? 'Revisa los proveedores seleccionados.';

            return;
        }

        if (!response.ok) {
            throw new Error(responseData.message ?? 'No fue posible iniciar el pago.');
        }

        if (!responseData.init_point) {
            throw new Error('MercadoPago no devolvió el enlace de pago de prueba.');
        }

        window.location.assign(responseData.init_point);
    } catch (error) {
        errorMessage.value = error.message || 'Ocurrió un problema de conexión.';
    } finally {
        confirmingPackageId.value = null;
    }
}

onMounted(loadPackages);
</script>

<template>
    <Head title="Seleccionar paquete" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-5xl space-y-7">
            <div class="flex flex-wrap items-end justify-between gap-5">
                <div>
                    <Link href="/events" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-blue-700">
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M16 10H4m5 5-5-5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        Volver a mis eventos
                    </Link>
                    <p class="mt-5 text-xs font-bold uppercase tracking-[0.16em] text-blue-600">Siguiente paso</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-950">Elige tu paquete de proveedores</h1>
                    <p class="mt-2 text-sm text-slate-600">{{ event.name }} · {{ event.category }}</p>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-white px-5 py-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-500">Presupuesto disponible para proveedores</p>
                    <p class="mt-1 text-xl font-bold text-slate-950">{{ formatCurrency(availableBudget) }}</p>
                    <p v-if="event.venue" class="mt-1 text-xs text-slate-500">Salón: {{ event.venue.name }}</p>
                </div>
            </div>

            <p v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                {{ errorMessage }}
            </p>

            <div v-if="isLoading" class="grid gap-5 md:grid-cols-2">
                <div v-for="placeholder in 2" :key="placeholder" class="h-72 animate-pulse rounded-3xl border border-slate-200 bg-white"></div>
            </div>

            <div v-else-if="packages.length" class="grid gap-5 md:grid-cols-2">
                <article v-for="packageOption in packages" :key="packageOption.id" class="flex flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-blue-600">{{ packageOption.category }}</p>
                            <h2 class="mt-1 text-xl font-bold text-slate-950">{{ packageOption.name }}</h2>
                        </div>
                        <span class="shrink-0 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Recomendado</span>
                    </div>
                    <p v-if="packageOption.description" class="mt-3 text-sm leading-6 text-slate-600">{{ packageOption.description }}</p>

                    <div v-if="packageOption.providers.length" class="mt-5 space-y-2 border-t border-slate-100 pt-4">
                        <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Incluye</p>
                        <label v-for="provider in packageOption.providers" :key="provider.id" class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-100 px-3 py-3 transition hover:border-blue-100 hover:bg-blue-50/50">
                            <input v-model="packageOption.selectedProviderIds" type="checkbox" :value="provider.id" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-slate-800">{{ provider.category }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ provider.name }}</span>
                            </span>
                            <span class="shrink-0 text-sm font-semibold text-slate-700">{{ formatCurrency(providerPrice(provider)) }}</span>
                        </label>
                    </div>
                    <p v-else class="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-500">Este paquete todavía no tiene proveedores asociados.</p>

                    <div class="mt-auto flex flex-wrap items-end justify-between gap-4 border-t border-slate-100 pt-5">
                        <div>
                            <p class="text-xs font-semibold text-slate-500">Precio real de referencia</p>
                            <p class="mt-1 text-2xl font-bold tracking-tight text-slate-950">{{ formatCurrency(packageTotal(packageOption)) }}</p>
                            <p class="mt-1 text-xs font-medium text-blue-700">Para pruebas, se cobrará $1 MXN</p>
                        </div>
                        <button
                            type="button"
                            :disabled="confirmingPackageId !== null || !packageOption.selectedProviderIds.length || packageTotal(packageOption) > availableBudget"
                            class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-600 disabled:cursor-not-allowed disabled:bg-slate-300"
                            @click="confirmPackage(packageOption)"
                        >
                            {{ confirmingPackageId === packageOption.id ? 'Preparando pago…' : 'Pagar $1 MXN' }}
                        </button>
                    </div>
                </article>
            </div>

            <section v-else class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center shadow-sm">
                <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-blue-50 text-blue-600">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="2.5" /><path d="M7.5 9h9m-9 4h6" /></svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-slate-950">Aún no hay paquetes recomendados</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-500">No encontramos paquetes de {{ event.category }} dentro de tu presupuesto disponible. Puedes volver a tus eventos y continuar después.</p>
                <Link href="/events" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-600">Volver a mis eventos</Link>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
