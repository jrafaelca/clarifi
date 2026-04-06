<script setup lang="ts">
import { Form, Head, router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';
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
import { index as categoriesIndex } from '@/routes/categories';
import { index, store } from '@/routes/transactions';
import type {
    AccountRecord,
    CategoryRecord,
    SelectOption,
    Team,
    TransactionRecord,
    WorkspaceSummary,
} from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    filters: {
        accountId?: number | null;
        categoryId?: number | null;
        type?: string | null;
        month: string;
    };
    summary: {
        income: string;
        expenses: string;
        net: string;
        count: number;
    };
    accounts: Array<{
        id: AccountRecord['id'];
        name: AccountRecord['name'];
        type: AccountRecord['type'];
        balance: string;
    }>;
    categories: Pick<CategoryRecord, 'id' | 'name' | 'type' | 'typeLabel' | 'isSystem'>[];
    transactions: TransactionRecord[];
    transactionTypes: SelectOption[];
    transactionStatuses: SelectOption[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeam = page.props.currentTeam as Team;
const createForm = reactive({
    type: 'expense',
});
const filterForm = reactive({
    month: props.filters.month,
    account_id: props.filters.accountId ? String(props.filters.accountId) : '',
    category_id: props.filters.categoryId ? String(props.filters.categoryId) : '',
    type: props.filters.type ?? '',
});

const applyFilters = () => {
    router.get(
        index({ current_team: currentTeam.slug }).url,
        {
            month: filterForm.month,
            account_id: filterForm.account_id || undefined,
            category_id: filterForm.category_id || undefined,
            type: filterForm.type || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Transactions',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Transactions" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Transactions"
            description="Capture income, expenses and transfers against your real accounts"
        />

        <div class="grid gap-4 md:grid-cols-4">
            <Card>
                <CardHeader>
                    <CardDescription>Income</CardDescription>
                    <CardTitle>
                        <CurrencyAmount :amount="summary.income" :currency="workspace.currency" />
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Expenses</CardDescription>
                    <CardTitle>
                        <CurrencyAmount :amount="summary.expenses" :currency="workspace.currency" />
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Net flow</CardDescription>
                    <CardTitle>
                        <CurrencyAmount :amount="summary.net" :currency="workspace.currency" />
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card>
                <CardHeader>
                    <CardDescription>Movements</CardDescription>
                    <CardTitle>{{ summary.count }}</CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <CardTitle>Register movement</CardTitle>
                    <CardDescription>
                        Need to fine-tune classifications?
                        <a
                            :href="categoriesIndex({ current_team: currentTeam.slug }).url"
                            class="underline underline-offset-4"
                        >
                            Manage categories
                        </a>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="store.form({ current_team: currentTeam.slug })"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="transaction-type">Type</Label>
                            <select
                                id="transaction-type"
                                v-model="createForm.type"
                                name="type"
                                class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option v-for="type in transactionTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div v-if="createForm.type === 'transfer'" class="grid gap-3 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="source-account">Source account</Label>
                                <select id="source-account" name="source_account_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="" disabled selected>Select account</option>
                                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                                        {{ account.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.source_account_id" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="destination-account">Destination account</Label>
                                <select id="destination-account" name="destination_account_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="" disabled selected>Select account</option>
                                    <option v-for="account in accounts" :key="account.id" :value="account.id">
                                        {{ account.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.destination_account_id" />
                            </div>
                        </div>

                        <div v-else class="grid gap-2">
                            <Label for="transaction-account">Account</Label>
                            <select id="transaction-account" name="account_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="" disabled selected>Select account</option>
                                <option v-for="account in accounts" :key="account.id" :value="account.id">
                                    {{ account.name }}
                                </option>
                            </select>
                            <InputError :message="errors.account_id" />
                        </div>

                        <div v-if="createForm.type !== 'transfer'" class="grid gap-2">
                            <Label for="transaction-category">Category</Label>
                            <select id="transaction-category" name="category_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="">Uncategorized</option>
                                <option
                                    v-for="category in categories.filter((item) => item.type === createForm.type)"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="errors.category_id" />
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="transaction-amount">Amount</Label>
                                <Input id="transaction-amount" type="number" name="amount" step="0.01" placeholder="0.00" />
                                <InputError :message="errors.amount" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="transaction-date">Date</Label>
                                <Input id="transaction-date" type="date" name="transaction_date" :default-value="new Date().toISOString().slice(0, 10)" />
                                <InputError :message="errors.transaction_date" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="transaction-description">Description</Label>
                            <Input id="transaction-description" name="description" placeholder="What happened?" />
                            <InputError :message="errors.description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="transaction-status">Status</Label>
                            <select id="transaction-status" name="status" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option v-for="status in transactionStatuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <div class="grid gap-2">
                            <Label for="transaction-notes">Notes</Label>
                            <textarea id="transaction-notes" name="notes" rows="3" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Optional details"></textarea>
                        </div>

                        <div class="grid gap-2">
                            <Label for="transaction-attachment">Attachment</Label>
                            <input id="transaction-attachment" name="attachment" type="file" class="block w-full text-sm" />
                            <InputError :message="errors.attachment" />
                        </div>

                        <Button :disabled="processing" class="w-full">Save movement</Button>
                    </Form>
                </CardContent>
            </Card>

            <div class="space-y-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 md:grid-cols-4">
                            <div class="grid gap-2">
                                <Label for="filter-month">Month</Label>
                                <Input id="filter-month" v-model="filterForm.month" type="month" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="filter-account">Account</Label>
                                <select id="filter-account" v-model="filterForm.account_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">All accounts</option>
                                    <option v-for="account in accounts" :key="account.id" :value="String(account.id)">
                                        {{ account.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="filter-category">Category</Label>
                                <select id="filter-category" v-model="filterForm.category_id" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">All categories</option>
                                    <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="filter-type">Type</Label>
                                <select id="filter-type" v-model="filterForm.type" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                    <option value="">All types</option>
                                    <option v-for="type in transactionTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <Button @click="applyFilters" type="button">Apply filters</Button>
                            <Button
                                @click="
                                    filterForm.month = new Date().toISOString().slice(0, 7);
                                    filterForm.account_id = '';
                                    filterForm.category_id = '';
                                    filterForm.type = '';
                                    applyFilters();
                                "
                                type="button"
                                variant="outline"
                            >
                                Reset
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Recorded movements</CardTitle>
                        <CardDescription>
                            {{ filters.month }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="transaction in transactions"
                            :key="transaction.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">{{ transaction.description }}</p>
                                        <Badge variant="secondary">{{ transaction.typeLabel }}</Badge>
                                        <Badge v-if="transaction.hasAttachment" variant="outline">Attachment</Badge>
                                    </div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ transaction.accountName }}
                                        <span v-if="transaction.relatedAccountName">
                                            → {{ transaction.relatedAccountName }}
                                        </span>
                                        <span v-if="transaction.categoryName">
                                            · {{ transaction.categoryName }}
                                        </span>
                                    </p>
                                    <p v-if="transaction.notes" class="mt-2 text-sm text-muted-foreground">
                                        {{ transaction.notes }}
                                    </p>
                                </div>

                                <div class="text-right">
                                    <p class="font-medium">
                                        <CurrencyAmount :amount="transaction.amount" :currency="transaction.currency" />
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ transaction.transactionDate }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <p v-if="transactions.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No transactions match the current filters.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
