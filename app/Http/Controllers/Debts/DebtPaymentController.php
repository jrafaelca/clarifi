<?php

namespace App\Http\Controllers\Debts;

use App\Application\Debts\RecordDebtPayment;
use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Domain\Debts\Models\Debt;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debts\StoreDebtPaymentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DebtPaymentController extends Controller
{
    /**
     * Store a newly created debt payment.
     */
    public function store(
        StoreDebtPaymentRequest $request,
        Debt $debt,
        RecordDebtPayment $recordDebtPayment,
    ): RedirectResponse {
        Gate::authorize('update', $debt);

        $validated = $request->validated();

        $recordDebtPayment->handle($debt, [
            'account' => isset($validated['account_id']) ? Account::query()->findOrFail($validated['account_id']) : null,
            'category' => isset($validated['category_id']) ? Category::query()->findOrFail($validated['category_id']) : null,
            'amount' => $validated['amount'],
            'paid_on' => $validated['paid_on'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return back();
    }
}
