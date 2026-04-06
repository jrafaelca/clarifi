<?php

namespace App\Http\Controllers\Transactions;

use App\Application\Support\Money;
use App\Application\Transactions\RecordTransaction;
use App\Application\Transactions\RecordTransfer;
use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use App\Domain\Transactions\Models\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\StoreTransactionRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Display the transactions page.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam()->firstOrFail();
        $month = CarbonImmutable::createFromFormat(
            'Y-m',
            $request->string('month')->toString() ?: now()->format('Y-m'),
        ) ?: CarbonImmutable::now();

        $filters = [
            'accountId' => $request->integer('account_id') ?: null,
            'categoryId' => $request->integer('category_id') ?: null,
            'type' => $request->string('type')->toString() ?: null,
            'month' => $month->format('Y-m'),
        ];

        $transactions = Transaction::query()
            ->forTeam($team)
            ->with(['account', 'category', 'relatedAccount'])
            ->when($filters['accountId'], fn ($query, $accountId) => $query->where('account_id', $accountId))
            ->when($filters['categoryId'], fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['type'], fn ($query, $type) => $query->where('type', $type))
            ->whereBetween('transaction_date', [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()])
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        $income = $transactions
            ->where('type', TransactionType::Income)
            ->reduce(fn (int $carry, Transaction $transaction) => $carry + Money::toCents($transaction->amount), 0);

        $expenses = $transactions
            ->where('type', TransactionType::Expense)
            ->reduce(fn (int $carry, Transaction $transaction) => $carry + Money::toCents($transaction->amount), 0);

        return Inertia::render('transactions/Index', [
            'workspace' => [
                'currency' => $team->currency,
                'name' => $team->name,
            ],
            'filters' => $filters,
            'summary' => [
                'income' => Money::fromCents($income),
                'expenses' => Money::fromCents($expenses),
                'net' => Money::fromCents($income - $expenses),
                'count' => $transactions->count(),
            ],
            'accounts' => Account::query()
                ->forTeam($team)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Account $account) => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'type' => $account->type->value,
                    'balance' => $account->current_balance,
                ])
                ->values(),
            'categories' => Category::query()
                ->where(function ($query) use ($team) {
                    $query->whereNull('team_id')
                        ->orWhere('team_id', $team->id);
                })
                ->orderBy('type')
                ->orderBy('name')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'type' => $category->type->value,
                    'typeLabel' => $category->type->label(),
                    'isSystem' => $category->is_system,
                ])
                ->values(),
            'transactions' => $transactions->map(fn (Transaction $transaction) => [
                'id' => $transaction->id,
                'description' => $transaction->description,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'type' => $transaction->type->value,
                'typeLabel' => $transaction->type->label(),
                'direction' => $transaction->direction->value,
                'status' => $transaction->status->value,
                'transactionDate' => $transaction->transaction_date->toDateString(),
                'notes' => $transaction->notes,
                'accountName' => $transaction->account->name,
                'relatedAccountName' => $transaction->relatedAccount?->name,
                'categoryName' => $transaction->category?->name,
                'hasAttachment' => $transaction->attachment_path !== null,
            ])->values(),
            'transactionTypes' => collect(TransactionType::cases())
                ->map(fn (TransactionType $type) => [
                    'value' => $type->value,
                    'label' => $type->label(),
                ])
                ->values(),
            'transactionStatuses' => collect(TransactionStatus::cases())
                ->map(fn (TransactionStatus $status) => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ])
                ->values(),
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(
        StoreTransactionRequest $request,
        RecordTransaction $recordTransaction,
        RecordTransfer $recordTransfer,
    ): RedirectResponse {
        Gate::authorize('create', Transaction::class);

        $team = $request->user()->currentTeam()->firstOrFail();
        $validated = $request->validated();
        $attachmentPath = $request->file('attachment')?->store("transactions/{$team->id}");

        if ($validated['type'] === TransactionType::Transfer->value) {
            $recordTransfer->handle($team, [
                'source_account' => Account::query()->findOrFail($validated['source_account_id']),
                'destination_account' => Account::query()->findOrFail($validated['destination_account_id']),
                'amount' => $validated['amount'],
                'transaction_date' => $validated['transaction_date'],
                'description' => $validated['description'],
                'notes' => $validated['notes'] ?? null,
                'status' => TransactionStatus::from($validated['status'] ?? TransactionStatus::Confirmed->value),
                'attachment_path' => $attachmentPath,
            ]);

            return back();
        }

        $recordTransaction->handle($team, [
            'account' => Account::query()->findOrFail($validated['account_id']),
            'category' => isset($validated['category_id']) ? Category::query()->findOrFail($validated['category_id']) : null,
            'type' => TransactionType::from($validated['type']),
            'amount' => $validated['amount'],
            'transaction_date' => $validated['transaction_date'],
            'description' => $validated['description'],
            'notes' => $validated['notes'] ?? null,
            'status' => TransactionStatus::from($validated['status'] ?? TransactionStatus::Confirmed->value),
            'attachment_path' => $attachmentPath,
        ]);

        return back();
    }
}
