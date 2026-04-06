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
                title: 'Resumen',
                href: page.currentTeam ? dashboard(page.currentTeam.slug) : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Resumen" />

    <div class="space-y-8 px-4 py-6">
        <Heading
            title="Resumen financiero"
            :description="`${workspace.name} · ${workspace.month}`"
        />

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <MetricCard
                title="Saldo total"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.totalBalance))"
                description="En cuentas activas"
            />
            <MetricCard
                title="Ingresos del mes"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.incomeThisMonth))"
                description="Ingresos confirmados"
            />
            <MetricCard
                title="Gastos del mes"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.expensesThisMonth))"
                description="Gastos confirmados"
            />
            <MetricCard
                title="Presupuesto disponible"
                :value="new Intl.NumberFormat('es-CL', { style: 'currency', currency: workspace.currency }).format(Number(metrics.budgetRemaining))"
                description="Estado del presupuesto actual"
            />
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <Card>
                <CardHeader>
                    <CardTitle>Estado del presupuesto</CardTitle>
                    <CardDescription>
                        {{ budget.month }} · disponible
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
                                    Gastado
                                    <CurrencyAmount :amount="item.spent" :currency="budget.currency" />
                                    de
                                    <CurrencyAmount :amount="item.amount" :currency="budget.currency" />
                                </p>
                            </div>
                            <Badge :variant="item.isOverBudget ? 'destructive' : 'secondary'">
                                {{ item.isOverBudget ? 'Sobre presupuesto' : 'En linea' }}
                            </Badge>
                        </div>
                    </div>

                    <p v-if="budget.items.length === 0" class="text-sm text-muted-foreground">
                        Aun no hay presupuestos creados para este mes.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Actividad reciente</CardTitle>
                    <CardDescription>
                        Tus ultimos movimientos financieros
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
                        Aun no hay movimientos en este espacio de trabajo.
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Metas</CardTitle>
                    <CardDescription>
                        Ahorrado
                        <CurrencyAmount :amount="metrics.goalProgress" :currency="workspace.currency" />
                        de
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
                                    de
                                    <CurrencyAmount :amount="goal.targetAmount" :currency="goal.currency" />
                                </p>
                            </div>
                            <Badge variant="secondary">
                                {{ goal.status }}
                            </Badge>
                        </div>
                    </div>

                    <p v-if="goals.length === 0" class="text-sm text-muted-foreground">
                        Crea tu primera meta de ahorro para comenzar a seguir tu progreso.
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Deudas</CardTitle>
                    <CardDescription>
                        Saldo pendiente
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
                                    Vence {{ debt.dueDate ?? 'Sin fecha' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium">
                                    <CurrencyAmount :amount="debt.currentBalance" :currency="debt.currency" />
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Minimo
                                    <CurrencyAmount :amount="debt.minimumPayment" :currency="debt.currency" />
                                </p>
                            </div>
                        </div>
                    </div>

                    <p v-if="debts.length === 0" class="text-sm text-muted-foreground">
                        Aun no tienes deudas registradas.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
