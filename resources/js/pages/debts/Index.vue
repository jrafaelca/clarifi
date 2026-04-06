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
import { index, store, update } from '@/routes/debts';
import { store as storePayment } from '@/routes/debts/payments';
import type { DebtRecord, Team, WorkspaceSummary } from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    accounts: Array<{ id: number; name: string }>;
    expenseCategories: Array<{ id: number; name: string }>;
    debts: DebtRecord[];
};

defineProps<Props>();

const page = usePage();
const currentTeam = page.props.currentTeam as Team;

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Debts',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Debts" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Debts"
            description="Track balances, minimum payments and repayment activity in one place"
        />

        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <CardTitle>Create debt</CardTitle>
                    <CardDescription>
                        Separate liabilities from operational accounts in the MVP.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="store.form({ current_team: currentTeam.slug })"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="debt-name">Name</Label>
                            <Input id="debt-name" name="name" placeholder="Student loan" />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="debt-lender">Lender</Label>
                            <Input id="debt-lender" name="lender" placeholder="Institution or issuer" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="debt-original">Original amount</Label>
                                <Input id="debt-original" type="number" name="original_amount" step="0.01" placeholder="0.00" />
                                <InputError :message="errors.original_amount" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="debt-current">Current balance</Label>
                                <Input id="debt-current" type="number" name="current_balance" step="0.01" placeholder="Leave empty to match original amount" />
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="debt-rate">Interest rate</Label>
                                <Input id="debt-rate" type="number" name="interest_rate" step="0.01" placeholder="0.00" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="debt-minimum">Minimum payment</Label>
                                <Input id="debt-minimum" type="number" name="minimum_payment" step="0.01" placeholder="0.00" />
                                <InputError :message="errors.minimum_payment" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="debt-due-date">Due date</Label>
                            <Input id="debt-due-date" type="date" name="due_date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="debt-notes">Notes</Label>
                            <textarea id="debt-notes" name="notes" rows="3" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Terms or notes"></textarea>
                        </div>

                        <Button :disabled="processing" class="w-full">Save debt</Button>
                    </Form>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card v-for="debt in debts" :key="debt.id">
                    <CardHeader>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle class="text-lg">{{ debt.name }}</CardTitle>
                                <CardDescription>
                                    {{ debt.lender ?? 'No lender' }} · due {{ debt.dueDate ?? 'not set' }}
                                </CardDescription>
                            </div>

                            <Badge variant="secondary">
                                {{ debt.status }}
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="rounded-lg bg-muted/40 p-4">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">Current balance</p>
                                <p class="mt-1 font-semibold">
                                    <CurrencyAmount :amount="debt.currentBalance" :currency="debt.currency" />
                                </p>
                            </div>
                            <div class="rounded-lg bg-muted/40 p-4">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">Minimum payment</p>
                                <p class="mt-1 font-semibold">
                                    <CurrencyAmount :amount="debt.minimumPayment" :currency="debt.currency" />
                                </p>
                            </div>
                            <div class="rounded-lg bg-muted/40 p-4">
                                <p class="text-xs uppercase tracking-wide text-muted-foreground">Interest rate</p>
                                <p class="mt-1 font-semibold">{{ debt.interestRate }}%</p>
                            </div>
                        </div>

                        <Form
                            v-bind="update.form({ current_team: currentTeam.slug, debt: debt.id })"
                            class="grid gap-3 md:grid-cols-2"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-2">
                                <Label :for="`debt-name-${debt.id}`">Name</Label>
                                <Input :id="`debt-name-${debt.id}`" name="name" :default-value="debt.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`debt-lender-${debt.id}`">Lender</Label>
                                <Input :id="`debt-lender-${debt.id}`" name="lender" :default-value="debt.lender ?? ''" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`debt-original-${debt.id}`">Original amount</Label>
                                <Input :id="`debt-original-${debt.id}`" name="original_amount" type="number" step="0.01" :default-value="debt.originalAmount" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`debt-current-${debt.id}`">Current balance</Label>
                                <Input :id="`debt-current-${debt.id}`" name="current_balance" type="number" step="0.01" :default-value="debt.currentBalance" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`debt-rate-${debt.id}`">Interest rate</Label>
                                <Input :id="`debt-rate-${debt.id}`" name="interest_rate" type="number" step="0.01" :default-value="debt.interestRate" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`debt-minimum-${debt.id}`">Minimum payment</Label>
                                <Input :id="`debt-minimum-${debt.id}`" name="minimum_payment" type="number" step="0.01" :default-value="debt.minimumPayment" />
                            </div>

                            <div class="grid gap-2">
                                <Label :for="`debt-due-date-${debt.id}`">Due date</Label>
                                <Input :id="`debt-due-date-${debt.id}`" name="due_date" type="date" :default-value="debt.dueDate ?? ''" />
                            </div>

                            <div class="grid gap-2 md:col-span-2">
                                <Label :for="`debt-notes-${debt.id}`">Notes</Label>
                                <textarea
                                    :id="`debt-notes-${debt.id}`"
                                    name="notes"
                                    rows="2"
                                    class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :value="debt.notes ?? ''"
                                ></textarea>
                            </div>

                            <div class="md:col-span-2">
                                <Button :disabled="processing" size="sm">Update debt</Button>
                                <InputError class="mt-2" :message="errors.name || errors.original_amount || errors.minimum_payment" />
                            </div>
                        </Form>

                        <div class="rounded-lg border p-4">
                            <h3 class="font-medium">Record payment</h3>
                            <Form
                                v-bind="storePayment.form({ current_team: currentTeam.slug, debt: debt.id })"
                                class="mt-4 grid gap-3 md:grid-cols-4"
                                v-slot="{ errors, processing }"
                            >
                                <select name="account_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">No linked account</option>
                                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                                        {{ account.name }}
                                    </option>
                                </select>

                                <select name="category_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">No category</option>
                                    <option v-for="category in expenseCategories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>

                                <Input type="number" name="amount" step="0.01" placeholder="Amount" />
                                <Input type="date" name="paid_on" :default-value="new Date().toISOString().slice(0, 10)" />

                                <textarea
                                    name="notes"
                                    rows="2"
                                    class="md:col-span-4 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    placeholder="Optional note"
                                ></textarea>

                                <div class="md:col-span-4 flex items-center gap-3">
                                    <Button :disabled="processing" size="sm">Add payment</Button>
                                    <InputError :message="errors.amount || errors.paid_on || errors.account_id || errors.category_id" />
                                </div>
                            </Form>
                        </div>

                        <div class="space-y-2">
                            <h3 class="font-medium">Payment history</h3>
                            <div
                                v-for="payment in debt.payments"
                                :key="payment.id"
                                class="flex items-start justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">
                                        <CurrencyAmount :amount="payment.amount" :currency="debt.currency" />
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ payment.accountName ?? 'No account' }} · {{ payment.paidOn }}
                                    </p>
                                </div>
                                <p v-if="payment.notes" class="max-w-xs text-right text-sm text-muted-foreground">
                                    {{ payment.notes }}
                                </p>
                            </div>

                            <p v-if="debt.payments.length === 0" class="text-sm text-muted-foreground">
                                No payments registered yet.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="debts.length === 0">
                    <CardContent class="py-10 text-center text-sm text-muted-foreground">
                        Create your first debt to track repayment progress.
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
