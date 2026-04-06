<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { index } from '@/routes/chat';
import { store as storeMessage } from '@/routes/chat/messages';
import type { ChatMessageRecord, Team, WorkspaceSummary } from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    providerConfigured: boolean;
    conversationId?: string | null;
    messages: ChatMessageRecord[];
    examplePrompts: string[];
};

const props = defineProps<Props>();
const page = usePage();
const currentTeam = computed(() => page.props.currentTeam as Team);

const conversationId = ref(props.conversationId ?? null);
const messages = ref<ChatMessageRecord[]>([...props.messages]);
const prompt = ref('');
const isSending = ref(false);
const errorMessage = ref<string | null>(null);
const fieldErrors = ref<{ prompt?: string }>({});

const hasMessages = computed(() => messages.value.length > 0);

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

const useExamplePrompt = (value: string) => {
    prompt.value = value;
};

const sendPrompt = async () => {
    const submittedPrompt = prompt.value.trim();

    if (!submittedPrompt || isSending.value || !currentTeam.value) {
        return;
    }

    isSending.value = true;
    errorMessage.value = null;
    fieldErrors.value = {};

    try {
        const response = await fetch(
            storeMessage({ current_team: currentTeam.value.slug }).url,
            {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    prompt: submittedPrompt,
                    conversation_id: conversationId.value,
                }),
            },
        );

        const payload = (await response.json()) as {
            message?: string;
            errors?: { prompt?: string[] };
            conversationId?: string | null;
            userMessage?: ChatMessageRecord;
            assistantMessage?: ChatMessageRecord;
        };

        if (!response.ok) {
            fieldErrors.value = {
                prompt: payload.errors?.prompt?.[0],
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

        conversationId.value = payload.conversationId ?? null;
        prompt.value = '';
    } catch {
        errorMessage.value = 'Un error de red interrumpio la respuesta del asistente.';
    } finally {
        isSending.value = false;
    }
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
            description="Capa conversacional de solo lectura sobre tus datos financieros reales"
        />

        <div class="grid gap-6 xl:grid-cols-[320px_1fr]">
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
                            {{ providerConfigured ? 'Proveedor configurado' : 'Proveedor sin configurar' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <Badge variant="outline">Solo lectura</Badge>
                        <Badge variant="outline">Datos reales del espacio</Badge>
                        <Badge variant="outline">Memoria conversacional</Badge>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        Pregunta por saldos, movimientos recientes, presupuestos, metas o deudas. El asistente puede inspeccionar tus datos reales, pero todavia no escribira ni confirmara cambios.
                    </p>

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
                        Tu hilo actual del espacio se mantiene anclado en datos de ClariFi y seguro por defecto.
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="min-h-[360px] space-y-3 rounded-xl border bg-muted/20 p-4">
                        <template v-if="hasMessages">
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
                        </template>

                        <div
                            v-else
                            class="flex min-h-[320px] items-center justify-center rounded-xl border border-dashed text-center text-sm text-muted-foreground"
                        >
                            Comienza con una pregunta sobre tus finanzas actuales y ClariFi respondera con los datos que ya existen en tu espacio.
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
                                placeholder="Como voy este mes?"
                            />
                            <p
                                v-if="fieldErrors.prompt"
                                class="text-sm text-destructive"
                            >
                                {{ fieldErrors.prompt }}
                            </p>
                        </label>

                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-muted-foreground">
                                El asistente seguira en modo solo lectura aunque le pidas crear o editar registros.
                            </p>
                            <Button
                                type="button"
                                :disabled="!providerConfigured || isSending || !prompt.trim()"
                                @click="sendPrompt"
                            >
                                {{ isSending ? 'Pensando...' : 'Enviar' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
