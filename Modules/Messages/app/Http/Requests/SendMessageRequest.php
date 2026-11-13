<?php

namespace Modules\Messages\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000'],
            'media_ids' => ['nullable', 'array', 'max:4'],
            'media_ids.*' => ['string', 'exists:media,id'],
            'reply_to_message_id' => ['nullable', 'string', 'exists:messages,id'],
        ];
    }
}
