<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountDeletionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'data_scope_type' => ['required', 'in:all,custom'],
            'data_scope_items' => ['required_if:data_scope_type,custom', 'nullable', 'array'],
            'data_scope_items.*' => ['string', 'in:posts,media,interactions,messages'],
            'reason' => ['nullable', 'string', 'max:100'],
            'details' => ['nullable', 'string', 'max:2000'],
            'confirm_deletion' => ['accepted'],
            'website_url' => ['nullable', 'string'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Please provide the email address associated with your account.',
            'email.email' => 'Please provide a valid email address.',
            'data_scope_items.required_if' => 'Please select at least one data category to delete.',
            'confirm_deletion.accepted' => 'You must acknowledge that account deletion is permanent.',
        ];
    }
}
