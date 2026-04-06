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
            errorMessage.value = payload.message ?? 'The assistant could not answer right now.';

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
        errorMessage.value = 'A network error interrupted the assistant response.';
    } finally {
        isSending.value = false;
    }
};

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Chat',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Chat" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Finance assistant"
            description="Read-only conversational layer over your real financial data"
        />

        <div class="grid gap-6 xl:grid-cols-[320px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <CardTitle>Workspace assistant</CardTitle>
                            <CardDescription>
                                {{ workspace.name }} · {{ workspace.currency }}
                            </CardDescription>
                        </div>
                        <Badge :variant="providerConfigured ? 'secondary' : 'outline'">
                            {{ providerConfigured ? 'Provider configured' : 'Provider not configured' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <Badge variant="outline">Read-only</Badge>
                        <Badge variant="outline">Real workspace data</Badge>
                        <Badge variant="outline">Conversation memory</Badge>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        Ask about balances, recent movements, budgets, goals, or debts. The assistant can inspect your real data but it will not write or confirm changes yet.
                    </p>

                    <div class="space-y-2">
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-muted-foreground">
                            Suggested prompts
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
                    <CardTitle>Conversation</CardTitle>
                    <CardDescription>
                        Your current workspace thread stays grounded in ClariFi data and remains safe by default.
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
                                        <span>{{ message.role === 'user' ? 'You' : 'ClariFi' }}</span>
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
                            Start with a question about your current finances and ClariFi will answer from the data already in your workspace.
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
                            <span class="text-sm font-medium">Message</span>
                            <textarea
                                v-model="prompt"
                                rows="4"
                                class="block w-full rounded-xl border border-input bg-background px-3 py-3 text-sm"
                                :disabled="!providerConfigured || isSending"
                                placeholder="How am I doing this month?"
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
                                The assistant will stay read-only even if you ask it to create or edit records.
                            </p>
                            <Button
                                type="button"
                                :disabled="!providerConfigured || isSending || !prompt.trim()"
                                @click="sendPrompt"
                            >
                                {{ isSending ? 'Thinking...' : 'Send' }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
