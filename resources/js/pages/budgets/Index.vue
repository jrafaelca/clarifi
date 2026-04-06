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
import { index, store, update } from '@/routes/budgets';
import type { BudgetStatus, Team, WorkspaceSummary } from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    month: string;
    status: BudgetStatus;
    categories: Array<{
        id: number;
        name: string;
        isSystem: boolean;
    }>;
};

defineProps<Props>();

const page = usePage();
const currentTeam = page.props.currentTeam as Team;

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Budgets',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Budgets" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Budgets"
            description="Compare planned spending against real expenses by category"
        />

        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <CardTitle>Set monthly budget</CardTitle>
                    <CardDescription>
                        Budgets are tracked per expense category and month.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="store.form({ current_team: currentTeam.slug })"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="budget-category">Category</Label>
                            <select id="budget-category" name="category_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="" disabled selected>Select category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="errors.category_id" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="budget-month">Month</Label>
                                <Input id="budget-month" type="month" name="month" :default-value="month" />
                                <InputError :message="errors.month" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="budget-amount">Amount</Label>
                                <Input id="budget-amount" type="number" name="amount" step="0.01" placeholder="0.00" />
                                <InputError :message="errors.amount" />
                            </div>
                        </div>

                        <Button :disabled="processing" class="w-full">Save budget</Button>
                    </Form>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>{{ status.month }}</CardTitle>
                        <CardDescription>
                            Budgeted
                            <CurrencyAmount :amount="status.totals.budgeted" :currency="status.currency" />
                            · Spent
                            <CurrencyAmount :amount="status.totals.spent" :currency="status.currency" />
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="budget in status.items"
                            :key="budget.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium">{{ budget.category.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        Remaining
                                        <CurrencyAmount :amount="budget.remaining" :currency="status.currency" />
                                    </p>
                                </div>
                                <Badge :variant="budget.isOverBudget ? 'destructive' : 'secondary'">
                                    {{ budget.isOverBudget ? 'Over budget' : 'On track' }}
                                </Badge>
                            </div>

                            <div class="mt-3 grid gap-3 md:grid-cols-2">
                                <div class="rounded-md bg-muted/40 p-3 text-sm">
                                    Budget:
                                    <CurrencyAmount :amount="budget.amount" :currency="status.currency" />
                                </div>
                                <div class="rounded-md bg-muted/40 p-3 text-sm">
                                    Spent:
                                    <CurrencyAmount :amount="budget.spent" :currency="status.currency" />
                                </div>
                            </div>

                            <Form
                                v-bind="update.form({ current_team: currentTeam.slug, budget: budget.id })"
                                class="mt-4 grid gap-3 md:grid-cols-[1fr_160px_auto]"
                                v-slot="{ processing, errors }"
                            >
                                <input type="hidden" name="month" :value="budget.month" />

                                <select name="category_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option v-for="category in categories" :key="category.id" :value="category.id" :selected="category.id === budget.category.id">
                                        {{ category.name }}
                                    </option>
                                </select>

                                <Input type="number" name="amount" step="0.01" :default-value="budget.amount" />

                                <Button :disabled="processing" size="sm">Update</Button>

                                <InputError class="md:col-span-3" :message="errors.amount || errors.category_id || errors.month" />
                            </Form>
                        </div>

                        <p v-if="status.items.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No budgets defined for this month yet.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
