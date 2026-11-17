<script setup lang="ts">
import axios from 'axios';
import { Download, FileSpreadsheet, Upload } from 'lucide-vue-next';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useNotification } from '@/composables/useNotification';

defineProps<{ open: boolean }>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    imported: [];
}>();

const notify = useNotification();
const fileInput = ref<HTMLInputElement | null>(null);
const file = ref<File | null>(null);
const importing = ref(false);
const result = ref<{ created: number; updated: number; skipped: number; errors: string[] } | null>(null);
const fieldErrors = ref<string[]>([]);

const reset = () => {
    file.value = null;
    result.value = null;
    fieldErrors.value = [];
    if (fileInput.value) fileInput.value.value = '';
};

const onOpenChange = (value: boolean) => {
    if (!value && importing.value) return;
    if (!value) reset();
    emit('update:open', value);
};

const onFileChange = (e: Event) => {
    file.value = (e.target as HTMLInputElement).files?.[0] ?? null;
    result.value = null;
    fieldErrors.value = [];
};

const downloadTemplate = () => {
    window.location.href = '/admin/users/import/template';
};

const runImport = async () => {
    if (!file.value) return;
    importing.value = true;
    fieldErrors.value = [];
    try {
        const formData = new FormData();
        formData.append('file', file.value);
        const response = await axios.post('/admin/users/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        const data = response.data ?? {};
        result.value = {
            created: data.created ?? 0,
            updated: data.updated ?? 0,
            skipped: data.skipped ?? 0,
            errors: Array.isArray(data.errors) ? data.errors : [],
        };
        notify.success('Importación completada');
        emit('imported');
    } catch (e: any) {
        if (e?.response?.data?.errors) {
            fieldErrors.value = Object.values(e.response.data.errors)
                .flat()
                .map((m) => String(m));
        } else {
            fieldErrors.value = [e?.response?.data?.message || 'Error en el cargue masivo de usuarios.'];
        }
        notify.error('No se pudo importar el archivo.');
    } finally {
        importing.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Importar usuarios</DialogTitle>
                <DialogDescription>
                    Sube un archivo Excel o CSV con: nombre, apellidos, identificación, género, correo,
                    celular, área, rol, fecha de nacimiento y fecha de contratación.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <Button variant="outline" size="sm" @click="downloadTemplate">
                    <Download /> Descargar plantilla de ejemplo
                </Button>

                <label
                    for="import-file"
                    class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed bg-muted/40 px-4 py-8 text-center transition-colors hover:bg-muted focus-within:ring-[3px] focus-within:ring-ring/40"
                >
                    <FileSpreadsheet class="size-6 text-muted-foreground" aria-hidden="true" />
                    <span class="text-sm font-medium">
                        {{ file ? file.name : 'Selecciona un archivo .xlsx, .xls o .csv' }}
                    </span>
                    <span class="text-xs text-muted-foreground">
                        {{ file ? 'Haz clic para cambiar el archivo' : 'Haz clic para buscarlo en tu equipo' }}
                    </span>
                    <input
                        id="import-file"
                        ref="fileInput"
                        type="file"
                        accept=".xlsx,.xls,.csv"
                        class="sr-only"
                        @change="onFileChange"
                    />
                </label>

                <div
                    v-if="result"
                    class="rounded-lg border border-success/25 bg-success-soft p-3 text-sm text-success"
                    role="status"
                >
                    Creados: <strong>{{ result.created }}</strong> · Actualizados:
                    <strong>{{ result.updated }}</strong> · Omitidos: <strong>{{ result.skipped }}</strong>
                </div>

                <div
                    v-if="result?.errors.length || fieldErrors.length"
                    class="max-h-40 overflow-y-auto rounded-lg border border-warning/25 bg-warning-soft p-3 text-xs text-warning"
                    role="alert"
                >
                    <p class="mb-1 font-semibold">Filas con observaciones</p>
                    <ul class="list-disc space-y-0.5 pl-4">
                        <li v-for="(err, i) in [...fieldErrors, ...(result?.errors ?? [])]" :key="i">
                            {{ err }}
                        </li>
                    </ul>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" :disabled="importing" @click="onOpenChange(false)">
                    {{ result ? 'Cerrar' : 'Cancelar' }}
                </Button>
                <Button :disabled="!file || importing" @click="runImport">
                    <Spinner v-if="importing" />
                    <Upload v-else />
                    {{ importing ? 'Importando…' : 'Importar' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
