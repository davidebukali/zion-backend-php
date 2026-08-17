<?php

namespace Modules\Interactions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Interactions\Enums\ReportReason;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::enum(ReportReason::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
