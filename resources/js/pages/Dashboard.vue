<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CurrencyAmount from '@/components/finance/CurrencyAmount.vue';
import MetricCard from '@/components/finance/MetricCard.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import type {
    BudgetStatus,
    DebtRecord,
    GoalRecord,
    TransactionRecord,
    WorkspaceSummary,
} from '@/types';

type Props = {
    workspace: WorkspaceSummary;
    metrics: {
        totalBalance: string;
        incomeThisMonth: string;
        expensesThisMonth: string;
        cashFlowThisMonth: string;
        budgetRemaining: string;
        goalProgress: string;
        goalTarget: string;
        debtBalance: string;
    };
    budget: BudgetStatus;
    goals: GoalRecord[];
    debts: DebtRecord[];
    latestTransactions: TransactionRecord[];
};

defineProps<Props>();

defineOptions({
    layout: (page: { currentTeam?: { slug: string } | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: page.currentTeam ? dashboard(page.currentTeam.slug) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-8 px-4 py-6">
        <Heading
            title="Financial overview"
            :description="`${workspace.name} · ${workspace.month}`"
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard
                title="Total balance"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.totalBalance))"
                description="Across active accounts"
            />
            <MetricCard
                title="Income this month"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.incomeThisMonth))"
                description="Confirmed income entries"
            />
            <MetricCard
                title="Expenses this month"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.expensesThisMonth))"
                description="Confirmed expense entries"
            />
            <MetricCard
                title="Budget remaining"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.budgetRemaining))"
                description="Current month budget status"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <Card>
                <CardHeader>
                    <CardTitle>Budget snapshot</CardTitle>
                    <CardDescription>
                        {{ budget.month }} · remaining
                        <CurrencyAmount :amount="budget.totals.remaining" :currency="budget.currency" />
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="item in budget.items.slice(0, 5)"
                        :key="item.id"
                        class="rounded-lg border p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ item.category.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Spent
                                    <CurrencyAmount :amount="item.spent" :currency="budget.currency" />
                                    of
                                    <CurrencyAmount :amount="item.amount" :currency="budget.currency" />
                                </p>
                            </div>
                            <Badge :variant="item.isOverBudget ? 'destructive' : 'secondary'">
                                {{ item.isOverBudget ? 'Over budget' : 'On track' }}
                            </Badge>
                        </div>
                    </div>

                    <p v-if="budget.items.length === 0" class="text-sm text-muted-foreground">
                        No budgets have been created for this month yet.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Latest activity</CardTitle>
                    <CardDescription>
                        Your most recent financial movements
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="transaction in latestTransactions"
                        :key="transaction.id"
                        class="flex items-start justify-between gap-3 rounded-lg border p-4"
                    >
                        <div>
                            <p class="font-medium">{{ transaction.description }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ transaction.accountName }}
                                <span v-if="transaction.categoryName">
                                    · {{ transaction.categoryName }}
                                </span>
                                <span v-if="transaction.relatedAccountName">
                                    · {{ transaction.relatedAccountName }}
                                </span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium">
                                <CurrencyAmount
                                    :amount="transaction.amount"
                                    :currency="transaction.currency"
                                />
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ transaction.transactionDate }}
                            </p>
                        </div>
                    </div>

                    <p v-if="latestTransactions.length === 0" class="text-sm text-muted-foreground">
                        No transactions yet for this workspace.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Goals</CardTitle>
                    <CardDescription>
                        Saved
                        <CurrencyAmount :amount="metrics.goalProgress" :currency="workspace.currency" />
                        of
                        <CurrencyAmount :amount="metrics.goalTarget" :currency="workspace.currency" />
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="goal in goals.slice(0, 4)"
                        :key="goal.id"
                        class="rounded-lg border p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ goal.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    <CurrencyAmount :amount="goal.currentAmount" :currency="goal.currency" />
                                    of
                                    <CurrencyAmount :amount="goal.targetAmount" :currency="goal.currency" />
                                </p>
                            </div>
                            <Badge variant="secondary">
                                {{ goal.status }}
                            </Badge>
                        </div>
                    </div>

                    <p v-if="goals.length === 0" class="text-sm text-muted-foreground">
                        Create your first savings goal to start tracking progress.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Debts</CardTitle>
                    <CardDescription>
                        Outstanding balance
                        <CurrencyAmount :amount="metrics.debtBalance" :currency="workspace.currency" />
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="debt in debts.slice(0, 4)"
                        :key="debt.id"
                        class="rounded-lg border p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium">{{ debt.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Due {{ debt.dueDate ?? 'No due date' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">
                                    <CurrencyAmount :amount="debt.currentBalance" :currency="debt.currency" />
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Minimum
                                    <CurrencyAmount :amount="debt.minimumPayment" :currency="debt.currency" />
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-if="debts.length === 0" class="text-sm text-muted-foreground">
                        You do not have debts registered yet.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
