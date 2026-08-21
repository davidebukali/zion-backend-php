<?php

namespace Modules\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateUploadUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'filename' => [
                'required',
                'string',
                'max:255',
            ],

            'mime_type' => [
                'required',
                'string',
                'in:image/jpeg,image/png,image/webp',
            ],

            'size' => [
                'required',
                'integer',
                'min:1',
                'max:10485760',
            ],
        ];
    }
}
