<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Auth\Enums\AccountType;
use Modules\Media\Enums\MediaStatus;

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
        $profile = $this->user()?->profile;
        $profileId = $profile?->id;
        $accountType = $this->input('account_type') ?? $profile?->account_type;

        return [
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('profiles', 'username')->ignore($profileId),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'account_type' => ['nullable', Rule::enum(AccountType::class)],
            'category' => [
                'nullable',
                'string',
                'max:255',
                Rule::in(AccountType::categoriesFor($accountType)),
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'church_id' => ['nullable', 'string', 'exists:churches,id'],
            'church_name' => ['nullable', 'string', 'max:255'],
            'church_location' => ['nullable', 'string', 'max:255'],
            'avatar_media_id' => [
                'nullable',
                'string',
                Rule::exists('media', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()?->id)
                        ->whereIn('status', [MediaStatus::Confirmed->value, MediaStatus::Processing->value, MediaStatus::Ready->value]);
                }),
            ],
            'cover_media_id' => [
                'nullable',
                'string',
                Rule::exists('media', 'id')->where(function ($query) {
                    $query->where('user_id', $this->user()?->id)
                        ->whereIn('status', [MediaStatus::Confirmed->value, MediaStatus::Processing->value, MediaStatus::Ready->value]);
                }),
            ],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'is_private' => ['nullable', 'boolean'],
        ];
    }
}
