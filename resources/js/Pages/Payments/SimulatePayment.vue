<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    type: { type: String, required: true },
    id: { type: Number, required: true },
    token: { type: String, required: true },
    title: { type: String, required: true },
    referenceAmount: { type: Number, required: true },
    chargeAmount: { type: Number, required: true },
});

const isConfirming = ref(false);
const errorMessage = ref('');

function formatCurrency(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 2,
    }).format(Number(value ?? 0));
}

function confirmPayment() {
    if (isConfirming.value) {
        return;
    }

    isConfirming.value = true;
    errorMessage.value = '';

    router.post('/simulate-payment/confirm', { type: props.type, id: props.id, token: props.token }, {
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] ?? 'No fue posible confirmar el pago simulado.';
        },
        onFinish: () => {
            isConfirming.value = false;
        },
    });
}
</script>

<template>
    <Head title="Confirmar pago de prueba" />

    <AuthenticatedLayout>
        <div class="fixed inset-0 z-50 grid place-items-center bg-slate-950/70 p-5" role="dialog" aria-modal="true" aria-labelledby="payment-title">
            <section class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-950/25 sm:p-9">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h3" /></svg>
                </div>
                <p class="mt-5 text-center text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Modo simulación</p>
                <h1 id="payment-title" class="mt-2 text-center text-2xl font-bold text-slate-950">Confirma tu pago</h1>
                <p class="mt-2 text-center text-sm text-slate-600">{{ title }}</p>

                <dl class="mt-7 space-y-4 rounded-2xl bg-slate-50 p-5">
                    <div class="flex items-center justify-between gap-4 text-sm">
                        <dt class="text-slate-600">Monto real de referencia</dt>
                        <dd class="font-semibold text-slate-900">{{ formatCurrency(referenceAmount) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-4 text-sm">
                        <dt class="font-semibold text-slate-800">Monto de prueba</dt>
                        <dd class="text-lg font-bold text-blue-700">{{ formatCurrency(chargeAmount) }}</dd>
                    </div>
                </dl>

                <p class="mt-4 text-center text-xs leading-5 text-slate-500">La confirmación es de prueba. Se guardará el estado del pago sin procesar un cargo real.</p>
                <p v-if="errorMessage" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ errorMessage }}</p>

                <div class="mt-7 flex flex-col-reverse gap-3 sm:flex-row">
                    <Link href="/events" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Cancelar</Link>
                    <button type="button" class="flex-1 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-wait disabled:opacity-60" :disabled="isConfirming" @click="confirmPayment">
                        {{ isConfirming ? 'Confirmando…' : 'Confirmar pago de prueba' }}
                    </button>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
