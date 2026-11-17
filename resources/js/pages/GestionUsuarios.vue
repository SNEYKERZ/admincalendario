<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageContainer from '@/components/app/PageContainer.vue';
import UsuariosManager from '@/components/UsuariosManager.vue';
import AppLayout from '@/layouts/AppLayout.vue';
</script>

<template>
    <Head title="Usuarios y vacaciones" />
    <AppLayout>
        <PageContainer>
            <UsuariosManager />
        </PageContainer>
    </AppLayout>
</template>
