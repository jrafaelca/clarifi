<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
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
import {
    archive,
    index,
    restore,
    store,
    update,
} from '@/routes/accounts';
import type { AccountRecord, SelectOption, Team, WorkspaceSummary } from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    accounts: AccountRecord[];
    accountTypes: SelectOption[];
};

const props = defineProps<Props>();

const page = usePage();
const currentTeam = page.props.currentTeam as Team;

defineOptions({
    layout: (page: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Accounts',
                href: page.currentTeam ? index({ current_team: page.currentTeam.slug }) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Accounts" />

    <div class="space-y-6 px-4 py-6">
        <Heading
            title="Accounts"
            description="Track where your money lives and keep balances aligned with your transactions"
        />

        <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
            <Card class="h-fit">
                <CardHeader>
                    <CardTitle>Create account</CardTitle>
                    <CardDescription>
                        All accounts operate in {{ workspace.currency }} for the MVP.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="store.form({ current_team: currentTeam.slug })"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="account-name">Name</Label>
                            <Input id="account-name" name="name" placeholder="Main checking" />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="account-type">Type</Label>
                            <select id="account-type" name="type" class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                                <option value="" disabled selected>Select a type</option>
                                <option v-for="type in accountTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="account-institution">Institution</Label>
                            <Input id="account-institution" name="institution" placeholder="Bank or issuer" />
                            <InputError :message="errors.institution" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="account-balance">Opening balance</Label>
                            <Input id="account-balance" type="number" name="initial_balance" step="0.01" placeholder="0.00" />
                            <InputError :message="errors.initial_balance" />
                        </div>

                        <Button :disabled="processing" class="w-full">Save account</Button>
                    </Form>
                </CardContent>
            </Card>

            <div class="grid gap-4 md:grid-cols-2">
                <Card v-for="account in props.accounts" :key="account.id" class="border-border/70">
                    <CardHeader class="space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <CardTitle class="text-lg">{{ account.name }}</CardTitle>
                                <CardDescription>
                                    {{ account.typeLabel }}
                                    <span v-if="account.institution"> · {{ account.institution }}</span>
                                </CardDescription>
                            </div>

                            <Badge :variant="account.isActive ? 'secondary' : 'outline'">
                                {{ account.isActive ? 'Active' : 'Archived' }}
                            </Badge>
                        </div>

                        <div class="rounded-lg bg-muted/40 p-4">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">
                                Current balance
                            </p>
                            <p class="mt-1 text-2xl font-semibold">
                                <CurrencyAmount :amount="account.currentBalance" :currency="account.currency" />
                            </p>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <Form
                            v-bind="update.form({ current_team: currentTeam.slug, account: account.id })"
                            class="space-y-3"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`account-name-${account.id}`">Name</Label>
                                    <Input :id="`account-name-${account.id}`" name="name" :default-value="account.name" />
                                    <InputError :message="errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`account-type-${account.id}`">Type</Label>
                                    <select
                                        :id="`account-type-${account.id}`"
                                        name="type"
                                        :value="account.type"
                                        class="block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option v-for="type in accountTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                    <InputError :message="errors.type" />
                                </div>
                            </div>

                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`account-institution-${account.id}`">Institution</Label>
                                    <Input
                                        :id="`account-institution-${account.id}`"
                                        name="institution"
                                        :default-value="account.institution ?? ''"
                                    />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`account-balance-${account.id}`">Opening balance</Label>
                                    <Input
                                        :id="`account-balance-${account.id}`"
                                        type="number"
                                        step="0.01"
                                        name="initial_balance"
                                        :default-value="account.initialBalance"
                                    />
                                    <InputError :message="errors.initial_balance" />
                                </div>
                            </div>

                            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                                <input type="checkbox" name="is_active" value="1" :checked="account.isActive" />
                                Keep this account active
                            </label>

                            <div class="flex flex-wrap gap-2">
                                <Button :disabled="processing" size="sm">
                                    Update
                                </Button>

                                <Link
                                    v-if="account.isActive"
                                    :href="archive({ current_team: currentTeam.slug, account: account.id })"
                                    method="patch"
                                    as="button"
                                    class="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium"
                                >
                                    Archive
                                </Link>

                                <Link
                                    v-else
                                    :href="restore({ current_team: currentTeam.slug, account: account.id })"
                                    method="patch"
                                    as="button"
                                    class="inline-flex h-9 items-center justify-center rounded-md border px-3 text-sm font-medium"
                                >
                                    Restore
                                </Link>
                            </div>
                        </Form>
                    </CardContent>
                </Card>

                <Card v-if="props.accounts.length === 0" class="md:col-span-2">
                    <CardContent class="py-10 text-center text-sm text-muted-foreground">
                        Create your first account to begin tracking balances and transactions.
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
