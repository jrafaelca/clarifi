<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import CurrencyAmount from '@/components/finance/CurrencyAmount.vue';
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
import { index, store, update } from '@/routes/goals';
import { store as storeContribution } from '@/routes/goals/contributions';
import type { GoalRecord, Team, WorkspaceSummary } from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    accounts: Array<{ id: number; name: string }>;
    goals: GoalRecord[];
};

defineProps<Props>();

const page = usePage();
const currentTeam = page.props.currentTeam as Team;

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Metas',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Metas" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Metas"
            description="Sigue tus objetivos de ahorro y el progreso de tus aportes en el tiempo"
        />

        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <CardTitle>Crear meta</CardTitle>
                    <CardDescription>
                        Las metas te ayudan a separar ahorro sin cambiar los saldos de tus cuentas.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="store.form({ current_team: currentTeam.slug })"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="goal-name">Nombre</Label>
                            <Input id="goal-name" name="name" placeholder="Fondo de emergencia" />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="goal-target">Monto objetivo</Label>
                                <Input id="goal-target" type="number" name="target_amount" step="0.01" placeholder="0.00" />
                                <InputError :message="errors.target_amount" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="goal-current">Monto actual</Label>
                                <Input id="goal-current" type="number" name="current_amount" step="0.01" placeholder="0.00" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="goal-date">Fecha objetivo</Label>
                            <Input id="goal-date" type="date" name="target_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="goal-notes">Notas</Label>
                            <textarea id="goal-notes" name="notes" rows="3" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Por que importa esta meta"></textarea>
                        </div>

                        <Button :disabled="processing" class="w-full">Guardar meta</Button>
                    </Form>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card v-for="goal in goals" :key="goal.id">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle class="text-lg">{{ goal.name }}</CardTitle>
                                <CardDescription>
                                    <CurrencyAmount :amount="goal.currentAmount" :currency="goal.currency" />
                                    de
                                    <CurrencyAmount :amount="goal.targetAmount" :currency="goal.currency" />
                                </CardDescription>
                            </div>

                            <Badge variant="secondary">
                                {{ goal.status }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <Form
                            v-bind="update.form({ current_team: currentTeam.slug, goal: goal.id })"
                            class="grid gap-3 md:grid-cols-2"
                            v-slot="{ processing, errors }"
                        >
                            <div class="grid gap-2">
                                <Label :for="`goal-name-${goal.id}`">Nombre</Label>
                                <Input :id="`goal-name-${goal.id}`" name="name" :default-value="goal.name" />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`goal-target-${goal.id}`">Monto objetivo</Label>
                                <Input :id="`goal-target-${goal.id}`" name="target_amount" type="number" step="0.01" :default-value="goal.targetAmount" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`goal-current-${goal.id}`">Monto actual</Label>
                                <Input :id="`goal-current-${goal.id}`" name="current_amount" type="number" step="0.01" :default-value="goal.currentAmount" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`goal-date-${goal.id}`">Fecha objetivo</Label>
                                <Input :id="`goal-date-${goal.id}`" name="target_date" type="date" :default-value="goal.targetDate ?? ''" />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label :for="`goal-notes-${goal.id}`">Notas</Label>
                                <textarea
                                    :id="`goal-notes-${goal.id}`"
                                    name="notes"
                                    rows="2"
                                    class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :value="goal.notes ?? ''"
                                ></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <Button :disabled="processing" size="sm">Actualizar meta</Button>
                            </div>
                        </Form>

                        <div class="rounded-lg border p-4">
                            <h3 class="font-medium">Agregar aporte</h3>
                            <Form
                                v-bind="storeContribution.form({ current_team: currentTeam.slug, goal: goal.id })"
                                class="mt-4 grid gap-3 md:grid-cols-4"
                                v-slot="{ errors, processing }"
                            >
                                <select name="account_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">Sin cuenta</option>
                                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                                        {{ account.name }}
                                    </option>
                                </select>

                                <Input type="number" name="amount" step="0.01" placeholder="Monto" />
                                <Input type="date" name="contributed_on" :default-value="new Date().toISOString().slice(0, 10)" />
                                <Button :disabled="processing" size="sm">Agregar</Button>

                                <textarea
                                    name="notes"
                                    rows="2"
                                    class="md:col-span-4 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Nota opcional"
                                ></textarea>

                                <InputError class="md:col-span-4" :message="errors.amount || errors.contributed_on || errors.account_id" />
                            </Form>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-medium">Aportes</h3>
                            <div
                                v-for="contribution in goal.contributions"
                                :key="contribution.id"
                                class="flex items-start justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">
                                        <CurrencyAmount :amount="contribution.amount" :currency="goal.currency" />
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ contribution.accountName ?? 'Sin cuenta' }} · {{ contribution.contributedOn }}
                                    </p>
                                </div>
                                <p v-if="contribution.notes" class="max-w-xs text-right text-sm text-muted-foreground">
                                    {{ contribution.notes }}
                                </p>
                            </div>

                            <p v-if="goal.contributions.length === 0" class="text-sm text-muted-foreground">
                                Aun no hay aportes.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="goals.length === 0">
                    <CardContent class="py-10 text-center text-sm text-muted-foreground">
                        Crea tu primera meta para comenzar a seguir tu progreso.
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
