<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
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
    <div class="auth-login-shell flex min-h-svh">
        <!-- Left side - Branding -->
        <div
            :class="[
                'relative hidden flex-col items-center justify-center p-10 text-white lg:flex lg:w-1/2',
                isDark
                    ? 'bg-gradient-to-br from-[#0d264f] via-[#15407f] to-[#0f6f7f]'
                    : 'bg-gradient-to-br from-[#0f3a8a] via-[#1f6fd2] to-[#1fb7b4]',
            ]"
        >
            <!-- Decorative elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div
                    class="absolute -top-36 -right-36 h-80 w-80 rounded-full bg-white/10 blur-3xl"
                ></div>
                <div
                    class="absolute -bottom-36 -left-36 h-80 w-80 rounded-full bg-emerald-300/20 blur-3xl"
                ></div>
                <div
                    class="absolute top-1/2 left-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-cyan-200/10 blur-3xl"
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

                <h2 class="mb-4 text-3xl font-semibold text-white/90">
                    Gestión de Ausencias y Vacaciones
                </h2>

                <p class="mb-8 text-lg leading-relaxed text-white/70">
                    Organiza ausencias, vacaciones y permisos sin complicaciones.
                    Desde colaboradores hasta administradores, todos pueden gestionar sus solicitudes,
                     consultar información y mantener el control del equipo con reportes y herramientas claras en un solo lugar.
                </p>

                <div
                    class="grid grid-cols-3 gap-6 text-center text-sm text-white/60"
                >
                    <div class="flex flex-col items-center gap-2">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl border border-white/20 bg-white/15"
                        >
                            <svg
                                class="h-6 w-6"
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
                            class="flex h-12 w-12 items-center justify-center rounded-xl border border-white/20 bg-white/15"
                        >
                            <svg
                                class="h-6 w-6"
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
                            class="flex h-12 w-12 items-center justify-center rounded-xl border border-white/20 bg-white/15"
                        >
                            <svg
                                class="h-6 w-6"
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
                        class="inline-flex items-center gap-2 rounded-full border border-[color:var(--auth-border)] bg-[color:var(--auth-surface)] px-3 py-1.5 text-xs font-medium text-[color:var(--auth-ink)] transition hover:bg-[color:var(--auth-surface-hover)]"
                        @click="setAuthTheme"
                    >
                        <span
                            class="inline-block h-2.5 w-2.5 rounded-full"
                            :class="
                                isDark ? 'bg-sky-300' : 'bg-[color:var(--auth-brand)]'
                            "
                        ></span>
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
                            class="auth-login-title text-2xl font-semibold tracking-tight"
                        >
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

<style scoped>
.auth-login-shell {
    --auth-brand: #1f67c9;
    --auth-brand-strong: #1b56a8;
    --auth-ink: #0f2e63;
    --auth-surface: #eff5ff;
    --auth-surface-hover: #e2edff;
    --auth-border: #bfd4fb;
    --primary: 217 74% 45%;
    --primary-foreground: 0 0% 100%;
    --ring: 217 74% 45%;
}

:global(.dark) .auth-login-shell {
    --auth-brand: #1f67c9;
    --auth-brand-strong: #1b56a8;
    --auth-ink: #d7e7ff;
    --auth-surface: #12213d;
    --auth-surface-hover: #19315d;
    --auth-border: #2b4779;
    --background: 218 45% 12%;
    --card: 218 39% 15%;
    --muted: 218 31% 21%;
    --border: 218 28% 28%;
    --primary: 217 74% 45%;
    --primary-foreground: 0 0% 100%;
    --ring: 217 74% 45%;
}

.auth-login-title {
    color: var(--auth-brand-strong);
}
</style>
