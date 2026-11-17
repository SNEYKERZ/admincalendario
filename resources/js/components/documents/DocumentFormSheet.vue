<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/app/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { useNotification } from '@/composables/useNotification';
import FileDropzone from './FileDropzone.vue';
import { DOCUMENT_STATUSES, DOCUMENT_TYPES, emptyDocumentForm } from './types';
import type {
    AreaOption,
    DocumentFormState,
    DocumentRow,
    TemplateOption,
    TemplateVariable,
    UserOption,
} from './types';

const props = defineProps<{
    open: boolean;
    document: DocumentRow | null;
    users: UserOption[];
    areas: AreaOption[];
    templates: TemplateOption[];
    templateVariables: TemplateVariable[];
    isAdmin: boolean;
    canManageTemplates: boolean;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    saved: [];
}>();

const notify = useNotification();
const form = ref<DocumentFormState>(emptyDocumentForm());
const errors = ref<Record<string, string>>({});
const saving = ref(false);

const isEditing = computed(() => !!props.document);

const selectedTemplate = computed(
    () =>
        props.templates.find((t) => String(t.id) === form.value.template_id) ??
        null,
);
const selectedTemplateHasContent = computed(
    () => !!selectedTemplate.value?.template_content?.trim(),
);

const fileRequirement = computed(() => {
    if (isEditing.value) return 'Opcional: sólo si quieres reemplazar el archivo actual.';
    if (form.value.document_mode === 'template' || form.value.template_id) {
        return 'Opcional si usas el contenido de la plantilla.';
    }
    return 'Requerido. PDF, Word o imagen.';
});

watch(
    () => props.open,
    (open) => {
        if (!open) return;
        errors.value = {};
        const doc = props.document;
        form.value = doc
            ? {
                  title: doc.title,
                  document_type: doc.document_type,
                  document_mode: doc.document_mode,
                  status: doc.status,
                  user_id: doc.user ? String(doc.user.id) : '',
                  template_id: doc.template ? String(doc.template.id) : '',
                  area_id: doc.area ? String(doc.area.id) : '',
                  start_date: doc.start_date ?? '',
                  end_date: doc.end_date ?? '',
                  expires_at: doc.expires_at ?? '',
                  notes: doc.notes ?? '',
                  template_content: doc.template_content ?? '',
                  file: null,
              }
            : emptyDocumentForm();
    },
    { immediate: true },
);

const insertTemplateVariable = (variableKey: string) => {
    const token = `{{${variableKey}}}`;
    const current = form.value.template_content;
    form.value.template_content = `${current}${current && !current.endsWith(' ') && !current.endsWith('\n') ? ' ' : ''}${token}`;
};

const validate = (): boolean => {
    const e: Record<string, string> = {};
    if (!form.value.title.trim()) e.title = 'Escribe un título para el documento.';

    const canUseTemplateContent =
        (props.isAdmin &&
            form.value.document_mode === 'template' &&
            form.value.template_content.trim().length > 0) ||
        (props.isAdmin &&
            form.value.document_mode !== 'template' &&
            form.value.template_id.length > 0 &&
            selectedTemplateHasContent.value);

    if (!isEditing.value && !form.value.file && !canUseTemplateContent) {
        e.file = 'Selecciona un archivo o usa una plantilla con contenido.';
    }
    if (props.isAdmin && form.value.document_mode !== 'template' && !form.value.user_id) {
        e.user_id = 'Selecciona el colaborador al que pertenece el documento.';
    }
    if (props.isAdmin && form.value.document_mode === 'derived' && !form.value.template_id) {
        e.template_id = 'Selecciona la plantilla base del documento derivado.';
    }
    if (form.value.start_date && form.value.end_date && form.value.end_date < form.value.start_date) {
        e.end_date = 'La fecha fin no puede ser anterior a la fecha inicio.';
    }
    errors.value = e;
    return Object.keys(e).length === 0;
};

const submit = async () => {
    if (!validate()) {
        notify.warning('Revisa los campos marcados.');
        return;
    }

    saving.value = true;
    try {
        const payload = new FormData();
        const f = form.value;
        payload.append('title', f.title);
        payload.append('document_type', f.document_type);
        payload.append('document_mode', f.document_mode);
        payload.append('status', f.status);
        if (f.user_id) payload.append('user_id', f.user_id);
        if (f.template_id) payload.append('template_id', f.template_id);
        if (f.area_id) payload.append('area_id', f.area_id);
        if (f.start_date) payload.append('start_date', f.start_date);
        if (f.end_date) payload.append('end_date', f.end_date);
        if (f.expires_at) payload.append('expires_at', f.expires_at);
        if (f.notes) payload.append('notes', f.notes);
        if (f.template_content.trim()) {
            payload.append('template_content', f.template_content.trim());
        }
        if (f.file) payload.append('file', f.file);

        const url = props.document ? `/documents/${props.document.id}` : '/documents';
        await axios.post(url, payload, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        notify.success(props.document ? 'Documento actualizado' : 'Documento creado');
        emit('update:open', false);
        emit('saved');
    } catch (error: any) {
        const serverErrors = error?.response?.data?.errors;
        if (serverErrors) {
            const mapped: Record<string, string> = {};
            Object.entries(serverErrors).forEach(([key, msgs]) => {
                mapped[key] = Array.isArray(msgs) ? String(msgs[0]) : String(msgs);
            });
            errors.value = mapped;
            notify.error('Revisa los campos marcados.');
        } else {
            notify.error(error?.response?.data?.message ?? 'No se pudo guardar el documento');
        }
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Sheet :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <SheetContent
            class="w-full gap-0 bg-card p-0 sm:max-w-xl"
            @interact-outside="(e: Event) => saving && e.preventDefault()"
        >
            <SheetHeader class="border-b px-5 py-4">
                <SheetTitle>
                    {{ isEditing ? 'Editar documento' : 'Nuevo documento' }}
                </SheetTitle>
                <SheetDescription>
                    {{
                        isAdmin
                            ? 'Asigna el documento a un colaborador o crea una plantilla general.'
                            : 'Sube un documento personal a tu expediente.'
                    }}
                </SheetDescription>
            </SheetHeader>

            <form
                id="document-form"
                class="flex-1 space-y-5 overflow-y-auto px-5 py-5"
                novalidate
                @submit.prevent="submit"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <FormField
                        label="Título"
                        for="doc-title"
                        required
                        class="sm:col-span-2"
                        :error="errors.title"
                    >
                        <Input
                            id="doc-title"
                            v-model="form.title"
                            :aria-invalid="!!errors.title"
                            placeholder="Ej. Contrato a término indefinido"
                        />
                    </FormField>

                    <FormField label="Tipo de documento" for="doc-type" :error="errors.document_type">
                        <select id="doc-type" v-model="form.document_type" class="input">
                            <option v-for="t in DOCUMENT_TYPES" :key="t.value" :value="t.value">
                                {{ t.label }}
                            </option>
                        </select>
                    </FormField>

                    <FormField label="Estado" for="doc-status" :error="errors.status">
                        <select id="doc-status" v-model="form.status" class="input">
                            <option v-for="s in DOCUMENT_STATUSES" :key="s.value" :value="s.value">
                                {{ s.label }}
                            </option>
                        </select>
                    </FormField>

                    <FormField
                        v-if="isAdmin"
                        label="Modalidad"
                        for="doc-mode"
                        class="sm:col-span-2"
                        :error="errors.document_mode"
                    >
                        <select id="doc-mode" v-model="form.document_mode" class="input">
                            <option value="user">Documento por usuario</option>
                            <option value="derived">Documento derivado de una plantilla</option>
                            <option v-if="canManageTemplates" value="template">
                                Documento general (plantilla)
                            </option>
                        </select>
                    </FormField>

                    <FormField
                        v-if="isAdmin && form.document_mode !== 'template'"
                        label="Colaborador"
                        for="doc-user"
                        required
                        :error="errors.user_id"
                    >
                        <select
                            id="doc-user"
                            v-model="form.user_id"
                            class="input"
                            :aria-invalid="!!errors.user_id"
                        >
                            <option value="">Selecciona un colaborador</option>
                            <option v-for="u in users" :key="u.id" :value="String(u.id)">
                                {{ u.name }}
                            </option>
                        </select>
                    </FormField>

                    <FormField
                        v-if="isAdmin && form.document_mode !== 'template'"
                        :label="form.document_mode === 'derived' ? 'Plantilla base' : 'Plantilla usada (opcional)'"
                        for="doc-template"
                        :required="form.document_mode === 'derived'"
                        :error="errors.template_id"
                        :hint="
                            form.template_id
                                ? selectedTemplateHasContent
                                    ? 'Las variables de la plantilla se llenarán con los datos del colaborador.'
                                    : 'Esta plantilla no tiene contenido: deberás subir un archivo.'
                                : undefined
                        "
                    >
                        <select
                            id="doc-template"
                            v-model="form.template_id"
                            class="input"
                            :aria-invalid="!!errors.template_id"
                        >
                            <option value="">
                                {{ form.document_mode === 'derived' ? 'Selecciona una plantilla' : 'Sin plantilla relacionada' }}
                            </option>
                            <option v-for="t in templates" :key="t.id" :value="String(t.id)">
                                {{ t.title }}
                            </option>
                        </select>
                    </FormField>

                    <FormField
                        v-if="isAdmin && form.document_mode === 'template'"
                        label="Área (opcional)"
                        for="doc-area"
                        class="sm:col-span-2"
                        hint="Déjalo en «Global» para que aplique a toda la empresa."
                        :error="errors.area_id"
                    >
                        <select id="doc-area" v-model="form.area_id" class="input">
                            <option value="">Global</option>
                            <option v-for="a in areas" :key="a.id" :value="String(a.id)">
                                {{ a.name }}
                            </option>
                        </select>
                    </FormField>
                </div>

                <fieldset class="grid gap-4 sm:grid-cols-3">
                    <legend class="sr-only">Vigencia</legend>
                    <FormField label="Fecha inicio" for="doc-start" :error="errors.start_date">
                        <Input id="doc-start" v-model="form.start_date" type="date" />
                    </FormField>
                    <FormField label="Fecha fin" for="doc-end" :error="errors.end_date">
                        <Input
                            id="doc-end"
                            v-model="form.end_date"
                            type="date"
                            :min="form.start_date || undefined"
                            :aria-invalid="!!errors.end_date"
                        />
                    </FormField>
                    <FormField label="Vence el" for="doc-expires" :error="errors.expires_at">
                        <Input id="doc-expires" v-model="form.expires_at" type="date" />
                    </FormField>
                </fieldset>

                <FormField label="Archivo" for="doc-file" :error="errors.file">
                    <FileDropzone
                        id="doc-file"
                        v-model="form.file"
                        :invalid="!!errors.file"
                        :hint="fileRequirement"
                        :current-file-name="document?.original_name"
                        @error="(msg: string | null) => (errors = { ...errors, file: msg ?? '' })"
                    />
                </FormField>

                <FormField label="Notas" for="doc-notes" :error="errors.notes">
                    <Textarea
                        id="doc-notes"
                        v-model="form.notes"
                        rows="3"
                        placeholder="Información adicional visible para administradores"
                    />
                </FormField>

                <div
                    v-if="isAdmin && form.document_mode === 'template'"
                    class="space-y-2"
                >
                    <FormField
                        label="Contenido de la plantilla"
                        for="doc-template-content"
                        :error="errors.template_content"
                    >
                        <template #default>
                            <p class="text-xs text-muted-foreground">
                                Inserta variables con un clic; se reemplazan por los datos del
                                colaborador al derivar el documento.
                            </p>
                            <div
                                v-if="templateVariables.length"
                                class="flex flex-wrap gap-1.5"
                            >
                                <button
                                    v-for="variable in templateVariables"
                                    :key="variable.key"
                                    type="button"
                                    class="rounded-md border bg-muted/50 px-2 py-0.5 font-mono text-xs text-foreground transition-colors hover:border-primary/40 hover:bg-primary-soft hover:text-primary"
                                    :title="variable.label"
                                    @click="insertTemplateVariable(variable.key)"
                                >
                                    <span v-text="'{{' + variable.key + '}}'"></span>
                                </button>
                            </div>
                            <Textarea
                                id="doc-template-content"
                                v-model="form.template_content"
                                rows="12"
                                class="font-mono text-[13px]"
                                placeholder="Escribe aquí el contrato plantilla…"
                            />
                        </template>
                    </FormField>
                </div>
            </form>

            <SheetFooter class="flex-row justify-end border-t px-5 py-3">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="saving"
                    @click="emit('update:open', false)"
                >
                    Cancelar
                </Button>
                <Button type="submit" form="document-form" :disabled="saving">
                    <Spinner v-if="saving" />
                    {{
                        saving
                            ? 'Guardando…'
                            : isEditing
                              ? 'Guardar cambios'
                              : 'Crear documento'
                    }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>
