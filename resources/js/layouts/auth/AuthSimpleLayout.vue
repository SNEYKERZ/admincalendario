<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh">
        <!-- Left side - Branding -->
        <div
            class="relative hidden flex-col items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 p-10 text-white lg:flex lg:w-1/2"
        >
            <!-- Decorative elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div
                    class="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-blue-500/20 blur-3xl"
                ></div>
                <div
                    class="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-indigo-500/20 blur-3xl"
                ></div>
                <div
                    class="absolute top-1/2 left-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-400/10 blur-3xl"
                ></div>
            </div>

            <div class="relative z-10 max-w-lg text-center">
                <div class="mb-8 flex justify-center">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl border border-white/20 bg-white/10 backdrop-blur-sm"
                        >
                            <AppLogoIcon class="size-8 text-white" />
                        </div>
                        <span class="text-3xl font-bold tracking-tight"
                            >AdminCalendar</span
                        >
                    </div>
                </div>

                <h2 class="mb-4 text-2xl font-semibold text-white/90">
                    Gestión de Ausencias y Vacaciones
                </h2>

                <p class="mb-8 text-lg leading-relaxed text-white/70">
                    Administra las ausencias y vacaciones de tus empleados de
                    manera eficiente. Controla permisos, ausente y vacaciones en
                    un solo lugar.
                </p>

                <div
                    class="grid grid-cols-3 gap-6 text-center text-sm text-white/60"
                >
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                        <span>Calendario</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                        <span>Equipos</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                        </div>
                        <span>Reportes</span>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-8 text-xs text-white/40">
                © 2026 AdminCalendar. Todos los derechos reservados.
            </div>
        </div>

        <!-- Right side - Form -->
        <div
            class="flex flex-1 flex-col items-center justify-center bg-background px-6 py-12"
        >
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <!-- Mobile logo -->
                    <div class="flex flex-col items-center gap-4 lg:hidden">
                        <Link
                            :href="home()"
                            class="flex flex-col items-center gap-2 font-medium"
                        >
                            <div
                                class="mb-1 flex h-11 w-11 items-center justify-center rounded-xl bg-primary"
                            >
                                <AppLogoIcon
                                    class="size-7 text-primary-foreground"
                                />
                            </div>
                            <span class="text-xl font-bold">AdminCalendar</span>
                        </Link>
                    </div>

                    <div class="space-y-2 text-center lg:text-left">
                        <h1 class="text-2xl font-semibold tracking-tight">
                            {{ title }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
