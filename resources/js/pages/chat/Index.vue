<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/chat';
import { approveAll as approveAllBatch, show as showBatch } from '@/routes/chat/ingestion-batches';
import { update as updateSuggestion } from '@/routes/chat/ingestion-suggestions';
import { store as storeMessage } from '@/routes/chat/messages';
import { edit as editWorkspace } from '@/routes/workspace';
import type {
    ChatMessageRecord,
    IngestionBatchRecord,
    IngestionSuggestionRecord,
    Team,
    WorkspaceSummary,
} from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    providerConfigured: boolean;
    canManageAi: boolean;
    conversationId?: string | null;
    messages: ChatMessageRecord[];
    ingestionBatches: IngestionBatchRecord[];
    examplePrompts: string[];
};

type ChatResponsePayload = {
    message?: string;
    errors?: Record<string, string[]>;
    conversationId?: string | null;
    userMessage?: ChatMessageRecord;
    assistantMessage?: ChatMessageRecord;
    batch?: IngestionBatchRecord;
};

const props = defineProps<Props>();
const page = usePage();
const currentTeam = computed(() => page.props.currentTeam as Team);
const conversationId = ref(props.conversationId ?? null);
const messages = ref<ChatMessageRecord[]>([...props.messages]);
const batches = ref<IngestionBatchRecord[]>([...props.ingestionBatches]);
const prompt = ref('');
const isSending = ref(false);
const errorMessage = ref<string | null>(null);
const fieldErrors = ref<Record<string, string | undefined>>({});
const selectedFiles = ref<File[]>([]);
const editingSuggestionId = ref<number | null>(null);
const editValues = ref<Record<string, string>>({});
let pollingHandle: number | null = null;

const hasMessages = computed(() => messages.value.length > 0);
const hasProcessingBatches = computed(() => batches.value.some((batch) => batch.status === 'processing'));
const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

const useExamplePrompt = (value: string) => {
    prompt.value = value;
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    selectedFiles.value = input.files ? Array.from(input.files) : [];
};

const resetComposer = () => {
    prompt.value = '';
    selectedFiles.value = [];
};

const upsertBatch = (batch: IngestionBatchRecord) => {
    const index = batches.value.findIndex((candidate) => candidate.id === batch.id);

    if (index === -1) {
        batches.value.unshift(batch);

        return;
    }

    batches.value.splice(index, 1, batch);
};

const removeSelectedFile = (index: number) => {
    selectedFiles.value.splice(index, 1);
};

const refreshBatch = async (batchId: number) => {
    if (!currentTeam.value) {
        return;
    }

    const response = await fetch(
        showBatch({ current_team: currentTeam.value.slug, ingestionBatch: batchId }).url,
        {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    );

    if (!response.ok) {
        return;
    }

    const payload = (await response.json()) as { batch: IngestionBatchRecord };

    upsertBatch(payload.batch);
};

const pollProcessingBatches = async () => {
    await Promise.all(
        batches.value
            .filter((batch) => batch.status === 'processing')
            .map((batch) => refreshBatch(batch.id)),
    );
};

const startPolling = () => {
    if (pollingHandle !== null || !hasProcessingBatches.value) {
        return;
    }

    pollingHandle = window.setInterval(() => {
        void pollProcessingBatches();
    }, 3000);
};

const stopPolling = () => {
    if (pollingHandle === null) {
        return;
    }

    window.clearInterval(pollingHandle);
    pollingHandle = null;
};

watch(hasProcessingBatches, (value) => {
    if (value) {
        startPolling();
        void pollProcessingBatches();

        return;
    }

    stopPolling();
}, { immediate: true });

onUnmounted(() => {
    stopPolling();
});

const sendPrompt = async () => {
    if (isSending.value || !currentTeam.value || (!prompt.value.trim() && selectedFiles.value.length === 0)) {
        return;
    }

    isSending.value = true;
    errorMessage.value = null;
    fieldErrors.value = {};

    const form = new FormData();

    if (prompt.value.trim()) {
        form.append('prompt', prompt.value.trim());
    }

    if (conversationId.value) {
        form.append('conversation_id', conversationId.value);
    }

    selectedFiles.value.forEach((file) => form.append('attachments[]', file));

    try {
        const response = await fetch(
            storeMessage({ current_team: currentTeam.value.slug }).url,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: form,
            },
        );

        const payload = (await response.json()) as ChatResponsePayload;

        if (!response.ok) {
            fieldErrors.value = {
                prompt: payload.errors?.prompt?.[0],
                attachments: payload.errors?.attachments?.[0] ?? payload.errors?.['attachments.0']?.[0],
            };
            errorMessage.value = payload.message ?? 'El asistente no pudo responder en este momento.';

            return;
        }

        if (payload.userMessage) {
            messages.value.push(payload.userMessage);
        }

        if (payload.assistantMessage) {
            messages.value.push(payload.assistantMessage);
        }

        if (payload.batch) {
            upsertBatch(payload.batch);
        }

        conversationId.value = payload.conversationId ?? null;
        resetComposer();
    } catch {
        errorMessage.value = 'Un error de red interrumpio la operacion del asistente.';
    } finally {
        isSending.value = false;
    }
};

const batchSuggestions = (batch: IngestionBatchRecord, kind: IngestionSuggestionRecord['kind']) =>
    batch.suggestions.filter((suggestion) => suggestion.kind === kind);

const beginEdit = (suggestion: IngestionSuggestionRecord) => {
    editingSuggestionId.value = suggestion.id;
    editValues.value = {
        name: String(suggestion.payload.name ?? ''),
        institution: String(suggestion.payload.institution ?? ''),
        type: String(suggestion.payload.type ?? ''),
        transaction_date: String(suggestion.payload.transaction_date ?? ''),
        description: String(suggestion.payload.description ?? ''),
        amount: String(suggestion.payload.amount ?? ''),
        notes: String(suggestion.payload.notes ?? ''),
        account_name: String(suggestion.payload.account_name ?? ''),
        category_name: String(suggestion.payload.category_name ?? ''),
    };
};

const stopEdit = () => {
    editingSuggestionId.value = null;
    editValues.value = {};
};

const submitSuggestionAction = async (
    batch: IngestionBatchRecord,
    suggestion: IngestionSuggestionRecord,
    action: 'approve' | 'reject',
) => {
    if (!currentTeam.value) {
        return;
    }

    errorMessage.value = null;

    const edits = action === 'approve' && editingSuggestionId.value === suggestion.id
        ? { ...editValues.value }
        : undefined;

    try {
        const response = await fetch(
            updateSuggestion({ current_team: currentTeam.value.slug, ingestionSuggestion: suggestion.id }).url,
            {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    action,
                    edits,
                }),
            },
        );

        const payload = (await response.json()) as { batch?: IngestionBatchRecord; message?: string };

        if (!response.ok || !payload.batch) {
            errorMessage.value = payload.message ?? 'No se pudo actualizar la sugerencia.';

            return;
        }

        upsertBatch(payload.batch);
        stopEdit();
    } catch {
        errorMessage.value = 'Un error de red impidio actualizar la sugerencia.';
    }
};

const approveBatch = async (batch: IngestionBatchRecord) => {
    if (!currentTeam.value) {
        return;
    }

    try {
        const response = await fetch(
            approveAllBatch({ current_team: currentTeam.value.slug, ingestionBatch: batch.id }).url,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            },
        );

        const payload = (await response.json()) as { batch?: IngestionBatchRecord; message?: string };

        if (!response.ok || !payload.batch) {
            errorMessage.value = payload.message ?? 'No se pudo aprobar el lote.';

            return;
        }

        upsertBatch(payload.batch);
    } catch {
        errorMessage.value = 'Un error de red impidio aprobar el lote.';
    }
};

const statusLabel = (status: string) => {
    switch (status) {
        case 'processing':
            return 'Procesando';
        case 'draft':
            return 'Borrador';
        case 'partially_confirmed':
            return 'Parcial';
        case 'confirmed':
            return 'Confirmado';
        case 'rejected':
            return 'Descartado';
        case 'failed':
            return 'Fallido';
        default:
            return status;
    }
};

const suggestionSummary = (suggestion: IngestionSuggestionRecord) => {
    if (suggestion.kind === 'account') {
        return `${suggestion.payload.name ?? 'Cuenta nueva'} · ${suggestion.payload.type ?? 'bank'}`;
    }

    if (suggestion.kind === 'category') {
        return `${suggestion.payload.name ?? 'Categoria nueva'} · ${suggestion.payload.type ?? 'expense'}`;
    }

    return `${suggestion.payload.description ?? 'Movimiento'} · ${suggestion.payload.amount ?? '0'} ${props.workspace.currency}`;
};

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Asistente',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Asistente" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Asistente financiero"
            description="Consultas sobre datos reales e ingesta asistida en borrador desde el mismo chat"
        />

        <div class="grid gap-6 xl:grid-cols-[340px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <CardTitle>Asistente del espacio</CardTitle>
                            <CardDescription>
                                {{ workspace.name }} · {{ workspace.currency }}
                            </CardDescription>
                        </div>
                        <Badge :variant="providerConfigured ? 'secondary' : 'outline'">
                            {{ providerConfigured ? 'IA lista' : 'IA pendiente' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <Badge variant="outline">Consultas con tools</Badge>
                        <Badge variant="outline">Archivos a borrador</Badge>
                        <Badge variant="outline">Confirmacion explicita</Badge>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        Puedes consultar el estado financiero del workspace o enviar texto, imagenes, PDFs y CSVs para generar sugerencias de cuentas, categorias y movimientos antes de guardarlos.
                    </p>

                    <div
                        v-if="!providerConfigured"
                        class="space-y-2 rounded-lg border border-dashed px-4 py-3 text-sm text-muted-foreground"
                    >
                        <p>
                            La IA del workspace aun no esta configurada.
                        </p>
                        <Link
                            v-if="canManageAi"
                            :href="editWorkspace().url"
                            class="text-sm font-medium text-foreground underline underline-offset-4"
                        >
                            Configurar IA del workspace
                        </Link>
                        <p v-else>
                            Pide a un propietario o administrador que conecte la clave desde Ajustes.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                            Sugerencias
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="examplePrompt in examplePrompts"
                                :key="examplePrompt"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-auto whitespace-normal text-left"
                                @click="useExamplePrompt(examplePrompt)"
                            >
                                {{ examplePrompt }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Conversacion</CardTitle>
                    <CardDescription>
                        El asistente puede responder con datos reales o dejar un lote en borrador para que lo revises inline.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="min-h-[420px] space-y-4 rounded-xl border bg-muted/20 p-4">
                        <template v-if="hasMessages || batches.length > 0">
                            <div
                                v-for="message in messages"
                                :key="`${message.role}-${message.id ?? message.content}`"
                                class="flex"
                                :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                            >
                                <div
                                    class="max-w-3xl space-y-2 rounded-2xl px-4 py-3 text-sm shadow-sm"
                                    :class="
                                        message.role === 'user'
                                            ? 'bg-primary text-primary-foreground'
                                            : 'border bg-background text-foreground'
                                    "
                                >
                                    <div class="flex items-center gap-2 text-[11px] uppercase tracking-[0.18em] opacity-70">
                                        <span>{{ message.role === 'user' ? 'Tu' : 'ClariFi' }}</span>
                                        <span v-if="message.createdAt">{{ message.createdAt }}</span>
                                    </div>
                                    <p class="whitespace-pre-wrap leading-6">
                                        {{ message.content }}
                                    </p>
                                    <div
                                        v-if="message.toolCalls.length > 0"
                                        class="flex flex-wrap gap-2 pt-1"
                                    >
                                        <Badge
                                            v-for="toolCall in message.toolCalls"
                                            :key="toolCall.id ?? toolCall.name"
                                            variant="secondary"
                                        >
                                            {{ toolCall.name }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-for="batch in batches"
                                :key="batch.id"
                                class="rounded-2xl border bg-background p-4 shadow-sm"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Badge :variant="batch.status === 'failed' ? 'outline' : 'secondary'">
                                                {{ statusLabel(batch.status) }}
                                            </Badge>
                                            <Badge variant="outline">{{ batch.sourceKind }}</Badge>
                                        </div>
                                        <p class="text-sm font-medium">
                                            {{ batch.summary ?? 'Procesando informacion para generar sugerencias...' }}
                                        </p>
                                        <p
                                            v-if="batch.errorMessage"
                                            class="text-sm text-destructive"
                                        >
                                            {{ batch.errorMessage }}
                                        </p>
                                    </div>

                                    <Button
                                        v-if="batch.status === 'draft' || batch.status === 'partially_confirmed'"
                                        type="button"
                                        size="sm"
                                        @click="approveBatch(batch)"
                                    >
                                        Aprobar todo
                                    </Button>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                    <span v-if="batch.files.length > 0">
                                        Archivos: {{ batch.files.map((file) => file.name).join(', ') }}
                                    </span>
                                    <span>
                                        {{ batch.counts.draft }} borradores · {{ batch.counts.approved }} aprobados · {{ batch.counts.rejected }} descartados
                                    </span>
                                </div>

                                <div
                                    v-if="batch.suggestions.length > 0"
                                    class="mt-4 space-y-4"
                                >
                                    <div
                                        v-for="kind in ['account', 'category', 'transaction']"
                                        :key="kind"
                                        class="space-y-2"
                                    >
                                        <div
                                            v-if="batchSuggestions(batch, kind as IngestionSuggestionRecord['kind']).length > 0"
                                            class="space-y-2"
                                        >
                                            <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                                                {{
                                                    kind === 'account'
                                                        ? 'Cuentas'
                                                        : kind === 'category'
                                                            ? 'Categorias'
                                                            : 'Movimientos'
                                                }}
                                            </p>

                                            <div
                                                v-for="suggestion in batchSuggestions(batch, kind as IngestionSuggestionRecord['kind'])"
                                                :key="suggestion.id"
                                                class="rounded-xl border p-3"
                                            >
                                                <div class="flex flex-wrap items-start justify-between gap-3">
                                                    <div class="space-y-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <p class="text-sm font-medium">
                                                                {{ suggestionSummary(suggestion) }}
                                                            </p>
                                                            <Badge variant="outline">
                                                                {{ statusLabel(suggestion.status) }}
                                                            </Badge>
                                                            <Badge
                                                                v-if="suggestion.confidence"
                                                                variant="secondary"
                                                            >
                                                                {{ suggestion.confidence }}
                                                            </Badge>
                                                        </div>
                                                        <p
                                                            v-if="suggestion.sourceExcerpt"
                                                            class="text-xs text-muted-foreground"
                                                        >
                                                            {{ suggestion.sourceExcerpt }}
                                                        </p>
                                                    </div>

                                                    <div
                                                        v-if="suggestion.status === 'draft'"
                                                        class="flex flex-wrap gap-2"
                                                    >
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            @click="beginEdit(suggestion)"
                                                        >
                                                            Editar
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            @click="submitSuggestionAction(batch, suggestion, 'approve')"
                                                        >
                                                            Aprobar
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            @click="submitSuggestionAction(batch, suggestion, 'reject')"
                                                        >
                                                            Rechazar
                                                        </Button>
                                                    </div>
                                                </div>

                                                <div
                                                    v-if="editingSuggestionId === suggestion.id"
                                                    class="mt-3 grid gap-3 md:grid-cols-2"
                                                >
                                                    <template v-if="suggestion.kind !== 'transaction'">
                                                        <div class="grid gap-2">
                                                            <Label>Nombre</Label>
                                                            <Input v-model="editValues.name" />
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label>Tipo</Label>
                                                            <Input v-model="editValues.type" />
                                                        </div>
                                                        <div
                                                            v-if="suggestion.kind === 'account'"
                                                            class="grid gap-2 md:col-span-2"
                                                        >
                                                            <Label>Institucion</Label>
                                                            <Input v-model="editValues.institution" />
                                                        </div>
                                                    </template>

                                                    <template v-else>
                                                        <div class="grid gap-2">
                                                            <Label>Fecha</Label>
                                                            <Input v-model="editValues.transaction_date" type="date" />
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label>Monto</Label>
                                                            <Input v-model="editValues.amount" />
                                                        </div>
                                                        <div class="grid gap-2 md:col-span-2">
                                                            <Label>Descripcion</Label>
                                                            <Input v-model="editValues.description" />
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label>Tipo</Label>
                                                            <Input v-model="editValues.type" />
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label>Cuenta</Label>
                                                            <Input v-model="editValues.account_name" />
                                                        </div>
                                                        <div class="grid gap-2">
                                                            <Label>Categoria</Label>
                                                            <Input v-model="editValues.category_name" />
                                                        </div>
                                                        <div class="grid gap-2 md:col-span-2">
                                                            <Label>Notas</Label>
                                                            <Input v-model="editValues.notes" />
                                                        </div>
                                                    </template>

                                                    <div class="md:col-span-2 flex flex-wrap gap-2">
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            @click="submitSuggestionAction(batch, suggestion, 'approve')"
                                                        >
                                                            Guardar y aprobar
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            @click="stopEdit"
                                                        >
                                                            Cancelar
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div
                            v-else
                            class="flex min-h-[360px] items-center justify-center rounded-xl border border-dashed text-center text-sm text-muted-foreground"
                        >
                            Haz una pregunta o sube un archivo para comenzar a poblar el workspace en borrador.
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-if="errorMessage"
                            class="rounded-lg border border-destructive/30 bg-destructive/5 px-3 py-2 text-sm text-destructive"
                        >
                            {{ errorMessage }}
                        </div>

                        <label class="grid gap-2">
                            <span class="text-sm font-medium">Mensaje</span>
                            <textarea
                                v-model="prompt"
                                rows="4"
                                class="block w-full rounded-xl border border-input bg-background px-3 py-3 text-sm"
                                :disabled="!providerConfigured || isSending"
                                placeholder="Como voy este mes o registra estos gastos..."
                            />
                            <InputError :message="fieldErrors.prompt" />
                        </label>

                        <div class="grid gap-2">
                            <Label for="attachments">Archivos</Label>
                            <Input
                                id="attachments"
                                type="file"
                                multiple
                                accept=".csv,.pdf,image/png,image/jpeg,image/webp"
                                :disabled="!providerConfigured || isSending"
                                @change="onFileChange"
                            />
                            <InputError :message="fieldErrors.attachments" />
                            <div
                                v-if="selectedFiles.length > 0"
                                class="flex flex-wrap gap-2"
                            >
                                <Badge
                                    v-for="(file, index) in selectedFiles"
                                    :key="`${file.name}-${index}`"
                                    variant="outline"
                                    class="gap-2"
                                >
                                    {{ file.name }}
                                    <button
                                        type="button"
                                        class="text-muted-foreground"
                                        @click="removeSelectedFile(index)"
                                    >
                                        x
                                    </button>
                                </Badge>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-muted-foreground">
                                El asistente consulta datos reales y toda ingesta se queda en borrador hasta que la apruebes.
                            </p>
                            <Button
                                type="button"
                                :disabled="!providerConfigured || isSending || (!prompt.trim() && selectedFiles.length === 0)"
                                @click="sendPrompt"
                            >
                                {{ isSending ? 'Procesando...' : 'Enviar' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
