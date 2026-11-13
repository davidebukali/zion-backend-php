<?php

namespace Modules\Posts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Posts\Enums\PostVisibility;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $post = $this->route('post');

        return $post && $this->user()?->can('update', $post);
    }

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
            'visibility' => [
                'nullable',
                Rule::enum(PostVisibility::class),
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
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $post = $this->route('post');
            if (! $post) {
                return;
            }

            // Determine effective content and media presence after the update
            $hasContentInput = $this->has('content');
            $newContent = $hasContentInput ? $this->input('content') : $post->content;

            $hasMediaInput = $this->has('media_ids');
            $hasMedia = $hasMediaInput
                ? ! empty($this->input('media_ids'))
                : $post->media()->exists();

            if (empty(trim((string) $newContent)) && ! $hasMedia) {
                $validator->errors()->add(
                    'content',
                    'A post must contain either text content or at least one media file.'
                );
            }
        });
    }
}
