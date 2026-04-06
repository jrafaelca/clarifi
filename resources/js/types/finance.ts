export type WorkspaceSummary = {
    name: string;
    currency: string;
    month?: string;
};

export type ChatToolCallRecord = {
    id?: string | null;
    name: string;
    arguments?: Record<string, unknown> | null;
};

export type ChatMessageRecord = {
    id?: string | null;
    role: 'user' | 'assistant';
    content: string;
    toolCalls: ChatToolCallRecord[];
    createdAt?: string | null;
};

export type SelectOption = {
    value: string;
    label: string;
};

export type AccountRecord = {
    id: number;
    name: string;
    type: string;
    typeLabel: string;
    currency: string;
    initialBalance: string;
    currentBalance: string;
    institution?: string | null;
    isActive: boolean;
    updatedAt?: string | null;
};

export type CategoryRecord = {
    id: number;
    name: string;
    type: string;
    typeLabel: string;
    parentId?: number | null;
    parentName?: string | null;
    icon?: string | null;
    color?: string | null;
    isSystem: boolean;
};

export type TransactionRecord = {
    id: number;
    description: string;
    amount: string;
    currency: string;
    type: string;
    typeLabel: string;
    direction: string;
    status: string;
    transactionDate: string;
    notes?: string | null;
    accountName: string;
    relatedAccountName?: string | null;
    categoryName?: string | null;
    hasAttachment: boolean;
};

export type BudgetStatusItem = {
    id: number;
    category: {
        id: number;
        name: string;
    };
    amount: string;
    spent: string;
    remaining: string;
    isOverBudget: boolean;
    month: string;
};

export type BudgetStatus = {
    month: string;
    currency: string;
    totals: {
        budgeted: string;
        spent: string;
        remaining: string;
    };
    items: BudgetStatusItem[];
};

export type GoalContributionRecord = {
    id: number;
    amount: string;
    contributedOn: string;
    accountName?: string | null;
    notes?: string | null;
};

export type GoalRecord = {
    id: number;
    name: string;
    targetAmount: string;
    currentAmount: string;
    currency: string;
    targetDate?: string | null;
    notes?: string | null;
    status: string;
    contributions: GoalContributionRecord[];
};

export type DebtPaymentRecord = {
    id: number;
    amount: string;
    paidOn: string;
    accountName?: string | null;
    notes?: string | null;
};

export type DebtRecord = {
    id: number;
    name: string;
    lender?: string | null;
    currency: string;
    originalAmount: string;
    currentBalance: string;
    interestRate: string;
    minimumPayment: string;
    dueDate?: string | null;
    status: string;
    notes?: string | null;
    payments: DebtPaymentRecord[];
};
