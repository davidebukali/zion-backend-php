<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $profileId = $this->user()?->profile?->id;

        return [
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('profiles', 'username')->ignore($profileId),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatar_media_id' => ['nullable', 'string', 'exists:media,id'],
            'cover_media_id' => ['nullable', 'string', 'exists:media,id'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
        ];
    }
}
