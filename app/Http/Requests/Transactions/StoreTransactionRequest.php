<?php

namespace App\Http\Requests\Transactions;

use App\Domain\Categories\Enums\CategoryType;
use App\Domain\Categories\Models\Category;
use App\Domain\Transactions\Enums\TransactionStatus;
use App\Domain\Transactions\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = $this->user()->currentTeam?->id;

        return [
            'type' => ['required', Rule::in(TransactionType::values())],
            'account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'source_account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'destination_account_id' => [
                'nullable',
                'different:source_account_id',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId),
                ),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(TransactionStatus::values())],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->input('type');

                if ($type === TransactionType::Transfer->value) {
                    if (! $this->filled('source_account_id')) {
                        $validator->errors()->add('source_account_id', 'The source account field is required for transfers.');
                    }

                    if (! $this->filled('destination_account_id')) {
                        $validator->errors()->add('destination_account_id', 'The destination account field is required for transfers.');
                    }

                    return;
                }

                if (! $this->filled('account_id')) {
                    $validator->errors()->add('account_id', 'The account field is required.');
                }

                if ($this->filled('category_id')) {
                    $category = Category::query()->find($this->integer('category_id'));

                    if ($category !== null) {
                        $expectedType = $type === TransactionType::Income->value
                            ? CategoryType::Income
                            : CategoryType::Expense;

                        if ($category->type !== $expectedType) {
                            $validator->errors()->add('category_id', 'The selected category does not match the transaction type.');
                        }
                    }
                }
            },
        ];
    }
}
