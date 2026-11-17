<script setup lang="ts">
import { FileText, UploadCloud, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { formatFileSize } from './types';

const props = withDefaults(
    defineProps<{
        modelValue: File | null;
        id?: string;
        accept?: string;
        /** Tamaño máximo en MB (sólo validación visual en cliente) */
        maxSizeMb?: number;
        invalid?: boolean;
        hint?: string;
        currentFileName?: string | null;
    }>(),
    { id: 'file-dropzone', maxSizeMb: 10 },
);

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
    error: [message: string | null];
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const dragging = ref(false);

const fileLabel = computed(() => props.modelValue?.name ?? null);

const setFile = (file: File | null) => {
    if (file && props.maxSizeMb && file.size > props.maxSizeMb * 1024 * 1024) {
        emit('error', `El archivo supera el máximo de ${props.maxSizeMb} MB.`);
        emit('update:modelValue', null);
        return;
    }
    emit('error', null);
    emit('update:modelValue', file);
};

const onChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    setFile(target.files?.[0] ?? null);
};

const onDrop = (e: DragEvent) => {
    dragging.value = false;
    const file = e.dataTransfer?.files?.[0] ?? null;
    if (file) setFile(file);
};

const clear = () => {
    if (inputRef.value) inputRef.value.value = '';
    setFile(null);
};

const openPicker = () => inputRef.value?.click();
</script>

<template>
    <div>
        <div
            v-if="!modelValue"
            role="button"
            tabindex="0"
            :aria-describedby="`${id}-hint`"
            :class="
                cn(
                    'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed px-4 py-6 text-center transition-colors outline-none hover:bg-muted/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/30',
                    dragging ? 'border-primary bg-primary-soft' : 'border-input',
                    invalid ? 'border-destructive' : '',
                )
            "
            @click="openPicker"
            @keydown.enter.prevent="openPicker"
            @keydown.space.prevent="openPicker"
            @dragover.prevent="dragging = true"
            @dragleave.prevent="dragging = false"
            @drop.prevent="onDrop"
        >
            <span
                class="flex size-9 items-center justify-center rounded-full bg-muted text-muted-foreground"
                aria-hidden="true"
            >
                <UploadCloud class="size-4" />
            </span>
            <p class="text-sm">
                <span class="font-medium text-primary">Selecciona un archivo</span>
                <span class="text-muted-foreground"> o arrástralo aquí</span>
            </p>
            <p :id="`${id}-hint`" class="text-xs text-muted-foreground">
                {{ hint ?? `Máximo ${maxSizeMb} MB` }}
            </p>
            <p
                v-if="currentFileName"
                class="text-xs text-muted-foreground"
            >
                Archivo actual: {{ currentFileName }}
            </p>
        </div>
        <div
            v-else
            class="flex items-center gap-3 rounded-lg border bg-muted/40 px-3 py-2.5"
        >
            <span
                class="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary-soft text-primary"
                aria-hidden="true"
            >
                <FileText class="size-4" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ fileLabel }}</p>
                <p class="text-xs text-muted-foreground">
                    {{ formatFileSize(modelValue.size) }}
                </p>
            </div>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                aria-label="Quitar archivo"
                @click="clear"
            >
                <X />
            </Button>
        </div>
        <input
            :id="id"
            ref="inputRef"
            type="file"
            class="sr-only"
            tabindex="-1"
            :accept="accept"
            @change="onChange"
        />
    </div>
</template>
