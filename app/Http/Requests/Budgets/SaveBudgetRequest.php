<?php

namespace App\Http\Requests\Budgets;

use App\Domain\Categories\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveBudgetRequest extends FormRequest
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
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->whereNull('team_id')->orWhere('team_id', $teamId),
                ),
            ],
            'month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'gt:0'],
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
                $categoryType = Category::query()
                    ->whereKey($this->input('category_id'))
                    ->value('type');

                if ($categoryType !== 'expense') {
                    $validator->errors()->add('category_id', 'Budgets can only be created for expense categories.');
                }
            },
        ];
    }
}
