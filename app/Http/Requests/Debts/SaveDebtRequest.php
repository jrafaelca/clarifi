<?php

namespace App\Http\Requests\Debts;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveDebtRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'lender' => ['nullable', 'string', 'max:120'],
            'original_amount' => ['required', 'numeric', 'gt:0'],
            'current_balance' => ['nullable', 'numeric', 'gte:0'],
            'interest_rate' => ['nullable', 'numeric', 'between:0,100'],
            'minimum_payment' => ['required', 'numeric', 'gte:0'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
