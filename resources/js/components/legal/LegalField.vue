<script setup lang="ts">
// Muestra un dato legal del negocio o un marcador visible si aún no se ha configurado en el .env.
defineProps<{
    value: string | null | undefined;
    label: string;
    email?: boolean;
}>();
</script>

<template>
    <a v-if="value && email" :href="`mailto:${value}`">{{ value }}</a>
    <span v-else-if="value">{{ value }}</span>
    <span v-else class="pending">[PENDIENTE: {{ label }}]</span>
</template>
