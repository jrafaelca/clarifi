<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { update as updateWorkspace } from '@/routes/workspace';
import { edit } from '@/routes/workspace';
import { destroy as destroyWorkspaceAi, update as updateWorkspaceAi } from '@/routes/workspace/ai';
import type { AiWorkspaceSettings, SelectOption, Team } from '@/types';

type Props = {
    workspace: Team;
    canManageWorkspace: boolean;
    currencyOptions: SelectOption[];
    aiSettings: AiWorkspaceSettings;
    aiModelOptions: SelectOption[];
};

const props = defineProps<Props>();

const currency = ref(props.workspace.currency);
const currencyError = ref<string | undefined>(undefined);
const currencyProcessing = ref(false);
const currencyRecentlySuccessful = ref(false);

const aiProvider = ref(props.aiSettings.provider);
const aiModel = ref(props.aiSettings.model);
const aiKey = ref('');
const aiErrors = ref<Record<string, string | undefined>>({});
const aiProcessing = ref(false);
const aiRecentlySuccessful = ref(false);
const aiRemoving = ref(false);

const submitCurrency = () => {
    if (!props.canManageWorkspace) {
        return;
    }

    currencyProcessing.value = true;
    currencyError.value = undefined;
    currencyRecentlySuccessful.value = false;

    router.patch(updateWorkspace().url, {
        currency: currency.value,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            currencyError.value = errors.currency;
        },
        onSuccess: () => {
            currencyRecentlySuccessful.value = true;
        },
        onFinish: () => {
            currencyProcessing.value = false;
        },
    });
};

const submitAiSettings = () => {
    if (!props.canManageWorkspace) {
        return;
    }

    aiProcessing.value = true;
    aiErrors.value = {};
    aiRecentlySuccessful.value = false;

    router.patch(updateWorkspaceAi().url, {
        ai_provider: aiProvider.value,
        ai_model: aiModel.value,
        openai_api_key: aiKey.value,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            aiErrors.value = errors;
        },
        onSuccess: () => {
            aiRecentlySuccessful.value = true;
            aiKey.value = '';
        },
        onFinish: () => {
            aiProcessing.value = false;
        },
    });
};

const removeAiSettings = () => {
    if (!props.canManageWorkspace || aiRemoving.value) {
        return;
    }

    aiRemoving.value = true;

    router.delete(destroyWorkspaceAi().url, {
        preserveScroll: true,
        onFinish: () => {
            aiRemoving.value = false;
        },
    });
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Espacio de trabajo',
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Espacio de trabajo" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Espacio de trabajo"
            description="Moneda operativa y configuracion compartida de IA para el workspace actual"
        />

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <CardTitle>{{ workspace.name }}</CardTitle>
                        <CardDescription>
                            Configuracion base del espacio donde ClariFi organiza tus finanzas.
                        </CardDescription>
                    </div>
                    <Badge variant="secondary">
                        {{ workspace.isPersonal ? 'Espacio personal' : 'Espacio compartido' }}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Slug
                        </p>
                        <p class="mt-1 font-medium">{{ workspace.slug }}</p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Moneda actual
                        </p>
                        <p class="mt-1 font-medium">{{ workspace.currency }}</p>
                    </div>
                </div>

                <p
                    v-if="!canManageWorkspace"
                    class="text-sm text-muted-foreground"
                >
                    Puedes ver el estado del workspace, pero solo propietarios y administradores pueden cambiar moneda o credenciales de IA.
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Moneda operativa</CardTitle>
                <CardDescription>
                    Cambia la moneda del workspace actual. ClariFi sincronizara la etiqueta de moneda en los registros financieros existentes.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-6" @submit.prevent="submitCurrency">
                    <div class="grid gap-2">
                        <Label for="currency">Moneda</Label>
                        <select
                            id="currency"
                            name="currency"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            v-model="currency"
                            :disabled="!canManageWorkspace || currencyProcessing"
                        >
                            <option
                                v-for="option in currencyOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="currencyError" />
                        <p class="text-sm text-muted-foreground">
                            Este cambio no convierte montos entre monedas. Solo redefine la moneda operativa del workspace.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="!canManageWorkspace || currencyProcessing">
                            Guardar moneda
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="currencyRecentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Moneda actualizada.
                            </p>
                        </Transition>
                    </div>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <CardTitle>IA del workspace</CardTitle>
                        <CardDescription>
                            La API key queda compartida por el equipo actual para consultas e ingesta asistida desde el chat.
                        </CardDescription>
                    </div>
                    <Badge :variant="aiSettings.configured ? 'secondary' : 'outline'">
                        {{ aiSettings.configured ? 'Configurada' : 'No configurada' }}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Provider
                        </p>
                        <p class="mt-1 font-medium uppercase">{{ aiSettings.provider }}</p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Modelo
                        </p>
                        <p class="mt-1 font-medium">{{ aiSettings.model }}</p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Credencial
                        </p>
                        <p class="mt-1 font-medium">
                            {{ aiSettings.configured ? `••••${aiSettings.keyLast4 ?? ''}` : 'Sin clave cargada' }}
                        </p>
                    </div>
                </div>

                <form
                    v-if="canManageWorkspace"
                    class="space-y-6"
                    @submit.prevent="submitAiSettings"
                >
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="ai_provider">Provider</Label>
                            <select
                                id="ai_provider"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                v-model="aiProvider"
                                :disabled="aiProcessing"
                            >
                                <option value="openai">OpenAI</option>
                            </select>
                            <InputError :message="aiErrors.ai_provider" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ai_model">Modelo</Label>
                            <select
                                id="ai_model"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                v-model="aiModel"
                                :disabled="aiProcessing"
                            >
                                <option
                                    v-for="option in aiModelOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError :message="aiErrors.ai_model" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="openai_api_key">OpenAI API key</Label>
                        <Input
                            id="openai_api_key"
                            v-model="aiKey"
                            type="password"
                            autocomplete="off"
                            placeholder="sk-proj-..."
                            :disabled="aiProcessing"
                        />
                        <InputError :message="aiErrors.openai_api_key" />
                        <p class="text-sm text-muted-foreground">
                            La clave se guarda cifrada y no se expone al frontend. Reemplazarla actualiza toda la IA del workspace.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <Button :disabled="aiProcessing">
                            {{ aiSettings.configured ? 'Actualizar IA' : 'Conectar IA' }}
                        </Button>

                        <Button
                            v-if="aiSettings.configured"
                            type="button"
                            variant="outline"
                            :disabled="aiRemoving"
                            @click="removeAiSettings"
                        >
                            Quitar configuracion
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="aiRecentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Configuracion IA actualizada.
                            </p>
                        </Transition>
                    </div>
                </form>

                <p
                    v-else
                    class="text-sm text-muted-foreground"
                >
                    Si necesitas usar el asistente o la ingesta de archivos y la IA aun no esta conectada, pide a un propietario o administrador que configure la clave del workspace aqui mismo.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
