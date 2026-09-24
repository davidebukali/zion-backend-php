<?php

namespace Modules\Posts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'content' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'media_ids' => [
                'nullable',
                'array',
                'max:10',
            ],

            'media_ids.*' => [
                'string',
                'distinct',
                'exists:media,id',
            ],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
