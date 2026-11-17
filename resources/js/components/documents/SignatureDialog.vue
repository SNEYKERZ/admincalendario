<script setup lang="ts">
import axios from 'axios';
import { Eraser, PenLine, Type } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';
import FormField from '@/components/app/FormField.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useNotification } from '@/composables/useNotification';
import { cn } from '@/lib/utils';
import type { DocumentRow } from './types';

const props = defineProps<{ document: DocumentRow | null }>();
const emit = defineEmits<{ close: []; signed: [] }>();

const notify = useNotification();
const method = ref<'draw' | 'type'>('draw');
const signatureName = ref('');
const canvasRef = ref<HTMLCanvasElement | null>(null);
const drawing = ref(false);
const hasDrawn = ref(false);
const submitting = ref(false);
const error = ref<string | null>(null);

const setupCanvas = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    // offsetWidth no se ve afectado por la animación de escala del diálogo
    const width = canvas.offsetWidth;
    const height = canvas.offsetHeight;
    canvas.width = Math.floor(width * ratio);
    canvas.height = Math.floor(height * ratio);
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a';
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);
    hasDrawn.value = false;
};

watch(
    () => props.document,
    async (doc) => {
        if (!doc) return;
        method.value = 'draw';
        signatureName.value = '';
        error.value = null;
        await nextTick();
        // Espera a que el diálogo termine de montarse
        requestAnimationFrame(setupCanvas);
    },
);

watch(method, async (m) => {
    error.value = null;
    if (m === 'draw') {
        await nextTick();
        setupCanvas();
    }
});

const point = (event: PointerEvent) => {
    const canvas = canvasRef.value;
    if (!canvas) return { x: 0, y: 0 };
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.offsetWidth / rect.width || 1;
    const scaleY = canvas.offsetHeight / rect.height || 1;
    return {
        x: (event.clientX - rect.left) * scaleX,
        y: (event.clientY - rect.top) * scaleY,
    };
};

const startDrawing = (event: PointerEvent) => {
    const canvas = canvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    drawing.value = true;
    canvas.setPointerCapture(event.pointerId);
    const { x, y } = point(event);
    ctx.beginPath();
    ctx.moveTo(x, y);
};

const draw = (event: PointerEvent) => {
    if (!drawing.value) return;
    const ctx = canvasRef.value?.getContext('2d');
    if (!ctx) return;
    const { x, y } = point(event);
    ctx.lineTo(x, y);
    ctx.stroke();
    hasDrawn.value = true;
    error.value = null;
};

const endDrawing = (event: PointerEvent) => {
    const canvas = canvasRef.value;
    const ctx = canvas?.getContext('2d');
    if (!canvas || !ctx) return;
    drawing.value = false;
    ctx.closePath();
    if (canvas.hasPointerCapture(event.pointerId)) {
        canvas.releasePointerCapture(event.pointerId);
    }
};

const submit = async () => {
    const doc = props.document;
    if (!doc) return;

    let payload: Record<string, string> = { signature_method: method.value };
    if (method.value === 'draw') {
        const data = canvasRef.value?.toDataURL('image/png');
        if (!data || !hasDrawn.value) {
            error.value = 'Dibuja tu firma en el recuadro.';
            return;
        }
        payload = { ...payload, signature_data: data };
    } else if (!signatureName.value.trim()) {
        error.value = 'Escribe tu nombre completo para firmar.';
        return;
    } else {
        payload = { ...payload, signature_name: signatureName.value.trim() };
    }

    submitting.value = true;
    try {
        await axios.post(`/documents/${doc.id}/sign`, payload);
        notify.success('Documento firmado correctamente');
        emit('signed');
        emit('close');
    } catch (e: any) {
        notify.error(e?.response?.data?.message ?? 'No fue posible firmar el documento');
    } finally {
        submitting.value = false;
    }
};

const onOpenChange = (open: boolean) => {
    if (!open && !submitting.value) emit('close');
};
</script>

<template>
    <Dialog :open="!!document" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Firmar documento</DialogTitle>
                <DialogDescription>
                    {{ document?.title }} · La firma queda registrada con fecha y hora.
                </DialogDescription>
            </DialogHeader>

            <div
                role="radiogroup"
                aria-label="Método de firma"
                class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1"
            >
                <button
                    v-for="opt in [
                        { value: 'draw', label: 'Dibujar', icon: PenLine },
                        { value: 'type', label: 'Escribir nombre', icon: Type },
                    ] as const"
                    :key="opt.value"
                    type="button"
                    role="radio"
                    :aria-checked="method === opt.value"
                    :class="
                        cn(
                            'flex h-8 items-center justify-center gap-1.5 rounded-md text-sm font-medium transition-colors',
                            method === opt.value
                                ? 'bg-card text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        )
                    "
                    @click="method = opt.value"
                >
                    <component :is="opt.icon" class="size-4" />
                    {{ opt.label }}
                </button>
            </div>

            <div v-if="method === 'draw'" class="space-y-2">
                <canvas
                    ref="canvasRef"
                    aria-label="Área para dibujar la firma con el mouse o el dedo"
                    :class="
                        cn(
                            'h-44 w-full touch-none rounded-lg border border-dashed bg-white',
                            error ? 'border-destructive' : 'border-input',
                        )
                    "
                    @pointerdown="startDrawing"
                    @pointermove="draw"
                    @pointerup="endDrawing"
                    @pointerleave="endDrawing"
                />
                <div class="flex items-center justify-between">
                    <p
                        v-if="error"
                        class="text-xs text-destructive"
                        role="alert"
                    >
                        {{ error }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Firma dentro del recuadro.
                    </p>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="setupCanvas"
                    >
                        <Eraser /> Limpiar
                    </Button>
                </div>
            </div>

            <div v-else class="space-y-3">
                <FormField label="Nombre completo" for="signature-name" :error="error">
                    <Input
                        id="signature-name"
                        v-model="signatureName"
                        placeholder="Escribe tu nombre para firmar"
                        :aria-invalid="!!error"
                        @keydown.enter.prevent="submit"
                    />
                </FormField>
                <p
                    class="rounded-lg border bg-muted/40 px-4 py-3 text-2xl text-foreground italic"
                    aria-live="polite"
                >
                    {{ signatureName || 'Tu firma se verá aquí' }}
                </p>
            </div>

            <DialogFooter>
                <Button
                    variant="outline"
                    :disabled="submitting"
                    @click="emit('close')"
                >
                    Cancelar
                </Button>
                <Button :disabled="submitting" @click="submit">
                    <Spinner v-if="submitting" />
                    {{ submitting ? 'Firmando…' : 'Confirmar firma' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
