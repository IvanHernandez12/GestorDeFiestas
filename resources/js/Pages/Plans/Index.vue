<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    plans: { type: Array, required: true },
    currentPlanId: { type: Number, default: null },
});

const isSubmitting = ref(false);
const errorMessage = ref('');

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
}

function subscribe(plan) {
    if (isSubmitting.value || plan.is_free || plan.id === props.currentPlanId) {
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    router.post('/plans/' + plan.id + '/subscribe', {}, {
        onError: (errors) => {
            errorMessage.value = errors.subscribe ?? 'No fue posible iniciar la suscripción.';
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Planes" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-6xl space-y-8">
            <header class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Planes</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Elige cómo quieres planear</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Compara los planes y amplía el espacio para organizar tus eventos.</p>
            </header>

            <p v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                {{ errorMessage }}
            </p>

            <div v-if="plans.length" class="grid gap-5 md:grid-cols-3">
                <article
                    v-for="plan in plans"
                    :key="plan.id"
                    class="flex flex-col rounded-3xl border bg-white p-6 shadow-sm"
                    :class="plan.slug === 'premium' ? 'border-blue-300 ring-4 ring-blue-50' : 'border-slate-200'"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-bold text-slate-950">{{ plan.name }}</h2>
                            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ formatCurrency(plan.price) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ plan.is_free ? 'Sin costo' : 'MXN · pago simulado' }}</p>
                        </div>
                        <span v-if="plan.slug === 'premium'" class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">Más completo</span>
                    </div>

                    <ul class="mt-7 flex-1 space-y-3 text-sm text-slate-600">
                        <li v-for="feature in plan.features" :key="feature" class="flex gap-2.5">
                            <span class="mt-0.5 text-emerald-600" aria-hidden="true">✓</span>
                            <span>{{ feature }}</span>
                        </li>
                    </ul>

                    <button
                        v-if="!plan.is_free"
                        type="button"
                        class="mt-8 w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isSubmitting || plan.id === currentPlanId"
                        @click="subscribe(plan)"
                    >
                        {{ plan.id === currentPlanId ? 'Plan actual' : isSubmitting ? 'Abriendo pago…' : 'Suscribirse' }}
                    </button>
                    <div v-else class="mt-8 rounded-xl bg-slate-100 px-4 py-3 text-center text-sm font-semibold text-slate-600">
                        {{ plan.id === currentPlanId ? 'Plan actual' : 'Disponible para comenzar' }}
                    </div>
                </article>
            </div>
            <div v-else class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" role="alert">
                Los planes todavía no están cargados. Reinicia la aplicación para ejecutar el seeder de planes.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
