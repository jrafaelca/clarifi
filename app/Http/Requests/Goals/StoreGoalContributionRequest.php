<?php

namespace App\Http\Requests\Goals;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalContributionRequest extends FormRequest
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
            'account_id' => [
                'nullable',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('team_id', $teamId)),
            ],
            'amount' => ['required', 'numeric', 'gt:0'],
            'contributed_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
