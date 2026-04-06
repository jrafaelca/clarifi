<?php

namespace App\Http\Requests\Chat;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChatMessageRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required_without:attachments', 'nullable', 'string', 'max:4000'],
            'attachments' => ['required_without:prompt', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf,text/csv,text/plain,application/csv,application/vnd.ms-excel',
            ],
            'conversation_id' => [
                'nullable',
                'string',
                'size:36',
                Rule::exists('agent_conversations', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->user()?->id),
                ),
            ],
        ];
    }
}
