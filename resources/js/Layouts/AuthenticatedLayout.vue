<script setup>
/**
 * @empresa Bigbitbyte
 * @autor Ivan Hernandez Hinojosa
 * @creado 24/noviembre/2026
 * @modificado 24/noviembre/2026
 * @descripción Define el layout autenticado y la navegación principal del organizador.
 */

import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const userInitials = computed(() => {
    const nameParts = (user.value?.name ?? 'Bigbitbyte').trim().split(/\s+/);

    return nameParts.slice(0, 2).map((part) => part.charAt(0)).join('').toUpperCase();
});
const isUserMenuOpen = ref(false);

function isActive(path) {
    return page.url.split('?')[0] === path;
}

function logOut() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white shadow-sm">
            <div class="mx-auto flex min-h-18 max-w-6xl items-center justify-between gap-5 px-5 sm:px-8">
                <Link href="/events" class="flex shrink-0 items-center gap-3" aria-label="Bigbitbyte, inicio">
                    <img :src="'/references/LogoBigBitByte.png'" alt="Logo Bigbitbyte" class="size-10 rounded-lg bg-slate-900 object-cover">
                    <span class="text-lg font-bold tracking-tight text-slate-950">Bigbitbyte</span>
                </Link>

                <nav class="hidden items-center gap-1 sm:flex">
                    <Link
                        href="/events"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                        :class="isActive('/events') ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'"
                    >Mis eventos</Link>
                    <Link
                        href="/events/create"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                        :class="isActive('/events/create') ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'"
                    >Crear evento</Link>
                </nav>

                <div class="relative flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-full p-1.5 pr-2.5 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100"
                        :aria-expanded="isUserMenuOpen"
                        aria-label="Abrir menú de usuario"
                        @click="isUserMenuOpen = !isUserMenuOpen"
                    >
                        <span class="grid size-9 place-items-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">{{ userInitials }}</span>
                        <span class="hidden max-w-36 truncate text-sm font-semibold text-slate-700 md:block">{{ user?.name ?? 'Organizador' }}</span>
                        <svg class="hidden size-4 text-slate-400 sm:block" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </button>
                    <div v-if="isUserMenuOpen" class="absolute right-0 top-full mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10">
                        <div class="border-b border-slate-100 px-3 py-2">
                            <p class="truncate text-sm font-semibold text-slate-900">{{ user?.name ?? 'Organizador' }}</p>
                            <p class="truncate text-xs text-slate-500">{{ user?.email ?? '' }}</p>
                        </div>
                        <button type="button" class="mt-1 w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-950" @click="logOut">
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-5 py-8 sm:px-8 sm:py-10">
            <slot />
        </main>
    </div>
</template>
