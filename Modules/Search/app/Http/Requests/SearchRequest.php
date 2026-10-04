<?php

namespace Modules\Search\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:255'],
            'type' => ['nullable', 'string', 'in:all,users,posts'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
