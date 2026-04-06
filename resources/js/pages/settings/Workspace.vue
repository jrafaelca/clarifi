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
import { Label } from '@/components/ui/label';
import { edit, update as updateWorkspace } from '@/routes/workspace';
import type { SelectOption, Team } from '@/types';

type Props = {
    workspace: Team;
    currencyOptions: SelectOption[];
};

const props = defineProps<Props>();

const currency = ref(props.workspace.currency);
const currencyError = ref<string | undefined>(undefined);
const processing = ref(false);
const recentlySuccessful = ref(false);

const submit = () => {
    processing.value = true;
    currencyError.value = undefined;
    recentlySuccessful.value = false;

    router.patch(updateWorkspace().url, {
        currency: currency.value,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            currencyError.value = errors.currency;
        },
        onSuccess: () => {
            recentlySuccessful.value = true;
        },
        onFinish: () => {
            processing.value = false;
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
            description="Tu espacio actual de ClariFi y su moneda operativa"
        />

        <Card>
            <CardHeader>
                <CardTitle>{{ workspace.name }}</CardTitle>
                <CardDescription>
                    El MVP usa un unico espacio personal con una sola moneda
                    operativa.
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 text-sm">
                <div class="flex items-center gap-2">
                    <span class="font-medium">Modo</span>
                    <Badge variant="secondary">
                        {{ workspace.isPersonal ? 'Espacio personal' : 'Espacio compartido' }}
                    </Badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Slug
                        </p>
                        <p class="mt-1 font-medium">{{ workspace.slug }}</p>
                    </div>

                    <div class="rounded-lg border p-4">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">
                            Moneda
                        </p>
                        <p class="mt-1 font-medium">{{ workspace.currency }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Moneda operativa</CardTitle>
                <CardDescription>
                    Cambia la moneda del workspace actual. ClariFi sincronizara la etiqueta de moneda en los registros financieros existentes del espacio.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="currency">Moneda</Label>
                        <select
                            id="currency"
                            name="currency"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            v-model="currency"
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
                            Este cambio no convierte montos entre monedas. Solo redefine la moneda operativa del workspace para mantener el MVP consistente.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">
                            Guardar moneda
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Moneda actualizada.
                            </p>
                        </Transition>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
