<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, Palette, ShieldCheck, UserRound } from 'lucide-vue-next';
import PageContainer from '@/components/app/PageContainer.vue';
import PageHeader from '@/components/app/PageHeader.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    { title: 'Mi perfil', href: editProfile(), icon: UserRound },
    { title: 'Contraseña', href: editPassword(), icon: KeyRound },
    { title: 'Doble factor (2FA)', href: show(), icon: ShieldCheck },
    { title: 'Apariencia', href: editAppearance(), icon: Palette },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <PageContainer class="max-w-5xl">
        <PageHeader
            title="Configuración de la cuenta"
            description="Administra tu perfil, la seguridad de tu cuenta y la apariencia."
        />

        <div class="flex flex-col gap-6 lg:flex-row lg:gap-10">
            <aside class="lg:w-52 lg:shrink-0">
                <nav
                    class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1 lg:flex-col lg:overflow-visible"
                    aria-label="Secciones de configuración"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :aria-current="
                            isCurrentOrParentUrl(item.href) ? 'page' : undefined
                        "
                        :class="
                            cn(
                                'flex shrink-0 items-center gap-2 rounded-md px-3 py-2 text-sm font-medium whitespace-nowrap transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/40 focus-visible:outline-none',
                                isCurrentOrParentUrl(item.href)
                                    ? 'bg-accent text-foreground'
                                    : 'text-muted-foreground hover:bg-accent/60 hover:text-foreground',
                            )
                        "
                    >
                        <component
                            :is="item.icon"
                            :class="
                                cn(
                                    'size-4',
                                    isCurrentOrParentUrl(item.href) &&
                                        'text-primary',
                                )
                            "
                        />
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <div class="min-w-0 flex-1">
                <section class="max-w-2xl space-y-10">
                    <slot />
                </section>
            </div>
        </div>
    </PageContainer>
</template>
