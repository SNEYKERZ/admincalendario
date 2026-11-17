<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BarChart3, CalendarDays, Moon, Sun, UsersRound } from 'lucide-vue-next';
import { computed } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

const setAuthTheme = () => {
    updateAppearance(isDark.value ? 'light' : 'dark');
};
</script>

<template>
    <div class="flex min-h-svh bg-background">
        <!-- Left side - Branding -->
        <div
            class="relative hidden flex-col items-center justify-center overflow-hidden bg-[oklch(0.33_0.12_274)] p-10 text-white lg:flex lg:w-1/2 dark:bg-[oklch(0.22_0.07_274)]"
        >
            <!-- Decorative elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div
                    class="absolute -top-36 -right-36 h-80 w-80 rounded-full bg-[oklch(0.6_0.17_274)]/40 blur-3xl"
                ></div>
                <div
                    class="absolute -bottom-36 -left-36 h-80 w-80 rounded-full bg-[oklch(0.55_0.15_250)]/30 blur-3xl"
                ></div>
                <div
                    class="absolute top-1/2 left-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/5 blur-3xl"
                ></div>
            </div>

            <div class="relative z-10 max-w-lg text-center">
                <div class="mb-8 flex justify-center">
                    <img
                        src="/logo-blanco.png"
                        alt="Ausentra"
                        class="h-38 w-auto object-contain drop-shadow-lg"
                    />
                </div>

                <h2 class="mb-4 text-3xl font-semibold tracking-[-0.02em] text-balance text-white">
                    Gestión de Ausencias/Novedades y Vacaciones
                </h2>

                <p class="mb-10 text-base leading-relaxed text-pretty text-white/80">
                    Organiza ausencias, vacaciones y permisos sin complicaciones.
                    Desde colaboradores hasta administradores, todos pueden gestionar sus solicitudes,
                     consultar información y mantener el control del equipo con reportes y herramientas claras en un solo lugar.
                </p>

                <div
                    class="grid grid-cols-3 gap-6 text-center text-sm text-white/90"
                >
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg border border-white/15 bg-white/10"
                        >
                            <CalendarDays class="size-5" aria-hidden="true" />
                        </div>
                        <span>Calendario</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg border border-white/15 bg-white/10"
                        >
                            <UsersRound class="size-5" aria-hidden="true" />
                        </div>
                        <span>Equipos</span>
                    </div>
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg border border-white/15 bg-white/10"
                        >
                            <BarChart3 class="size-5" aria-hidden="true" />
                        </div>
                        <span>Reportes</span>
                    </div>
                </div>
            </div>

            <div class="absolute bottom-8 text-xs text-white/70">
                © 2026 Ausentra. Todos los derechos reservados.
            </div>
        </div>

        <!-- Right side - Form -->
        <div
            class="flex flex-1 flex-col items-center justify-center bg-background px-6 py-12"
        >
            <div class="w-full max-w-sm">
                <div class="mb-5 flex justify-end">
                    <button
                        type="button"
                        class="inline-flex h-8 items-center gap-2 rounded-md border bg-card px-3 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        :aria-label="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                        @click="setAuthTheme"
                    >
                        <Sun v-if="isDark" class="size-3.5" aria-hidden="true" />
                        <Moon v-else class="size-3.5" aria-hidden="true" />
                        {{ isDark ? 'Modo claro' : 'Modo oscuro' }}
                    </button>
                </div>
                <div class="flex flex-col gap-8">
                    <!-- Mobile logo -->
                    <div class="flex flex-col items-center gap-4 lg:hidden">
                        <Link
                            :href="home()"
                            class="flex flex-col items-center gap-2 font-medium"
                        >
                            <img
                                src="/ausentra-logo.png"
                                alt="Ausentra"
                                class="h-34 w-auto object-contain"
                            />
                        </Link>
                    </div>

                    <div class="space-y-2 text-center lg:text-left">
                        <h1
                            class="text-2xl font-semibold tracking-[-0.02em] text-foreground"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>

                <nav
                    aria-label="Documentos legales"
                    class="mt-10 flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs text-muted-foreground"
                >
                    <a href="/legal/terms" class="underline-offset-4 hover:underline">Términos</a>
                    <a href="/legal/privacy" class="underline-offset-4 hover:underline">Privacidad</a>
                    <a href="/legal/cookies" class="underline-offset-4 hover:underline">Cookies</a>
                    <a href="/legal/refunds" class="underline-offset-4 hover:underline">Reembolsos</a>
                </nav>
            </div>
        </div>
    </div>
</template>


