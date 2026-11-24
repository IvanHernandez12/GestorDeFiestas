<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Presenta el formulario de registro web.
 */

import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = reactive({});
const processing = ref(false);
const requestError = ref('');

function clearErrors() {
    Object.keys(errors).forEach((field) => delete errors[field]);
    requestError.value = '';
}

async function submit() {
    clearErrors();
    processing.value = true;

    try {
        const response = await fetch('/api/register', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(form),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            Object.entries(payload.errors ?? {}).forEach(([field, messages]) => {
                errors[field] = messages[0];
            });
            requestError.value = payload.message ?? 'No se pudo crear la cuenta. Revisa los datos e inténtalo de nuevo.';
            processing.value = false;

            return;
        }

        if (!payload.token) {
            requestError.value = 'La cuenta se creó, pero no se recibió el token de acceso.';
            processing.value = false;

            return;
        }

        localStorage.setItem('auth_token', payload.token);

        router.post('/login', {
            email: form.email,
            password: form.password,
        }, {
            onError: () => {
                localStorage.removeItem('auth_token');
                requestError.value = 'La cuenta se creó, pero no se pudo iniciar la sesión web. Intenta iniciar sesión.';
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } catch {
        requestError.value = 'No se pudo conectar con el servidor. Inténtalo de nuevo.';
        processing.value = false;
    }
}
</script>

<template>
    <Head title="Crear cuenta" />

    <main class="relative grid min-h-screen place-items-center overflow-hidden bg-slate-950 px-5 py-12">
        <div class="pointer-events-none absolute -left-36 -top-40 size-[28rem] rounded-full bg-blue-500/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-44 -right-20 size-[32rem] rounded-full bg-violet-500/10 blur-3xl"></div>

        <div class="relative w-full max-w-md">
            <div class="mb-8 flex items-center justify-center gap-3 text-white">
                <span class="grid size-12 place-items-center rounded-2xl bg-blue-500 text-sm font-black tracking-tight shadow-lg shadow-blue-950/50">BB</span>
                <span class="text-xl font-bold tracking-tight">Bigbitbyte</span>
            </div>

            <section class="rounded-3xl border border-white/10 bg-white p-7 shadow-2xl shadow-black/30 sm:p-9">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">Gestor de eventos</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Crea tu cuenta</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Regístrate para empezar a organizar tus eventos.</p>

                <form class="mt-7 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nombre</label>
                        <input id="name" v-model="form.name" type="text" autocomplete="name" required autofocus maxlength="255" placeholder="Tu nombre" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                        <p v-if="errors.name" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.name }}</p>
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                        <input id="email" v-model="form.email" type="email" autocomplete="email" required maxlength="255" placeholder="tu@correo.com" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                        <p v-if="errors.email" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Contraseña</label>
                        <input id="password" v-model="form.password" type="password" autocomplete="new-password" required maxlength="255" placeholder="Mínimo 8 caracteres" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                        <p v-if="errors.password" class="mt-1.5 text-xs font-medium text-rose-600">{{ errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-700">Confirmar contraseña</label>
                        <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required maxlength="255" placeholder="Repite tu contraseña" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                    </div>

                    <p v-if="requestError" role="alert" class="rounded-xl bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ requestError }}</p>

                    <button type="submit" :disabled="processing" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-wait disabled:opacity-70">
                        {{ processing ? 'Creando cuenta…' : 'Crear cuenta' }}
                        <svg v-if="!processing" class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    ¿Ya tienes cuenta?
                    <Link href="/login" class="font-semibold text-blue-600 transition hover:text-blue-700">Inicia sesión</Link>
                </p>
            </section>

            <p class="mt-6 text-center text-xs text-slate-500">Bigbitbyte · Gestor de eventos</p>
        </div>
    </main>
</template>
