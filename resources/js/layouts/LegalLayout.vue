<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { LegalInfo } from '@/types/legal';

const props = defineProps<{
    title: string;
    legal: LegalInfo;
}>();

const page = usePage();
const isLoggedIn = computed(() => Boolean((page.props as any).auth?.user));

const links = [
    { href: '/legal/terms', label: 'Términos y condiciones' },
    { href: '/legal/privacy', label: 'Política de privacidad' },
    { href: '/legal/cookies', label: 'Política de cookies' },
    { href: '/legal/refunds', label: 'Reembolsos y retracto' },
];

const updatedAt = computed(() => {
    const [y, m, d] = props.legal.updated_at.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('es-CO', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
});
</script>

<template>
    <Head :title="title" />
    <div class="min-h-svh bg-background text-foreground">
        <a
            href="#contenido"
            class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2 focus:text-gray-900"
        >
            Saltar al contenido
        </a>

        <header class="border-b border-border">
            <div
                class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-4 px-4 py-4"
            >
                <Link href="/" class="flex items-center">
                    <img
                        src="/ausentra-logo.png"
                        :alt="`${legal.product_name} - ir al inicio`"
                        class="h-10 w-auto dark:hidden"
                    />
                    <img
                        src="/logo-blanco.png"
                        :alt="`${legal.product_name} - ir al inicio`"
                        class="hidden h-10 w-auto dark:block"
                    />
                </Link>
                <Link
                    :href="isLoggedIn ? '/dashboard' : '/login'"
                    class="rounded-md text-sm font-medium text-blue-700 underline underline-offset-4 hover:text-blue-900 focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:outline-none dark:text-blue-300 dark:hover:text-blue-200"
                >
                    {{ isLoggedIn ? 'Volver al panel' : 'Iniciar sesión' }}
                </Link>
            </div>
            <nav
                aria-label="Documentos legales"
                class="mx-auto max-w-4xl px-4 pb-3"
            >
                <ul class="flex flex-wrap gap-x-4 gap-y-2 text-sm">
                    <li v-for="link in links" :key="link.href">
                        <Link
                            :href="link.href"
                            class="rounded text-gray-700 underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:outline-none dark:text-gray-300"
                            :class="{
                                'font-semibold text-gray-900 underline dark:text-white':
                                    page.url === link.href,
                            }"
                            :aria-current="
                                page.url === link.href ? 'page' : undefined
                            "
                        >
                            {{ link.label }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </header>

        <main id="contenido" class="mx-auto max-w-4xl px-4 py-10">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-50">
                {{ title }}
            </h1>
            <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                Última actualización: {{ updatedAt }} · Versión
                {{ legal.version }}
            </p>

            <div
                class="legal-content mt-8 space-y-8 text-base leading-relaxed text-gray-800 dark:text-gray-200"
            >
                <slot />
            </div>
        </main>

        <footer class="border-t border-border">
            <div
                class="mx-auto max-w-4xl px-4 py-6 text-sm text-gray-700 dark:text-gray-300"
            >
                © {{ new Date().getFullYear() }}
                {{ legal.company_name || legal.product_name }}. Todos los
                derechos reservados.
            </div>
        </footer>
    </div>
</template>

<style scoped>
.legal-content :deep(h2) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}
.legal-content :deep(h3) {
    font-weight: 600;
    margin-top: 1rem;
    margin-bottom: 0.25rem;
}
.legal-content :deep(p + p),
.legal-content :deep(ul),
.legal-content :deep(ol),
.legal-content :deep(table) {
    margin-top: 0.75rem;
}
.legal-content :deep(ul) {
    list-style: disc;
    padding-left: 1.5rem;
}
.legal-content :deep(ol) {
    list-style: decimal;
    padding-left: 1.5rem;
}
.legal-content :deep(li + li) {
    margin-top: 0.35rem;
}
.legal-content :deep(a) {
    color: #1d4ed8;
    text-decoration: underline;
    text-underline-offset: 3px;
}
:global(.dark) .legal-content :deep(a) {
    color: #93c5fd;
}
.legal-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}
.legal-content :deep(th),
.legal-content :deep(td) {
    border: 1px solid var(--border, #d1d5db);
    padding: 0.5rem;
    text-align: left;
    vertical-align: top;
}
.legal-content :deep(.pending) {
    background: #fef3c7;
    color: #78350f;
    padding: 0 0.25rem;
    border-radius: 0.25rem;
    font-weight: 600;
}
</style>
