<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Presenta el formulario de inicio de sesión web.
 */

import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

function submit() {
    form.post('/login');
}
</script>

<template>
    <Head title="Iniciar sesión" />

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
                <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Te damos la bienvenida</h1>
                <p class="mt-2 text-sm leading-6 text-slate-500">Inicia sesión para continuar con la organización de tus eventos.</p>

                <form class="mt-7 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                        <input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus placeholder="tu@correo.com" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                        <p v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-semibold text-slate-700">Contraseña</label>
                        <input id="password" v-model="form.password" type="password" autocomplete="current-password" required placeholder="Tu contraseña" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-400 focus:ring-4 focus:ring-blue-50">
                        <p v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.password }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-wait disabled:opacity-70">
                        {{ form.processing ? 'Ingresando…' : 'Iniciar sesión' }}
                        <svg v-if="!form.processing" class="size-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10h12m-5-5 5 5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </button>
                </form>
            </section>

            <p class="mt-6 text-center text-xs text-slate-500">Bigbitbyte · Gestor de eventos</p>
        </div>
    </main>
</template>
