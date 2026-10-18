<?php

namespace Modules\Glitter\AuditLog\Http\Requests;

use App\Enums\ActivityLogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'log_type' => ['nullable', 'array'],
            'log_type.*' => ['string', Rule::in(ActivityLogType::values())],
            'action' => ['nullable', 'string', 'max:50'],
            'actor' => ['nullable', 'string', 'max:255'],
            'resource_type' => ['nullable', 'string', 'max:191'],
            'resource_id' => ['nullable', 'integer', 'min:1'],
            'source_type' => ['nullable', 'string', Rule::in(['core', 'module', 'plugin', 'template', 'unknown'])],
            'source_identifier' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('log_type'))) {
            $this->merge(['log_type' => [$this->input('log_type')]]);
        }
    }

    public function messages(): array
    {
        return [
            'log_type.*.in' => __('glitter-audit_log::messages.validation.log_type'),
            'action.max' => __('glitter-audit_log::messages.validation.action_max'),
            'source_type.in' => __('glitter-audit_log::messages.validation.source_type'),
            'source_identifier.regex' => __('glitter-audit_log::messages.validation.source_identifier'),
            'date_to.after_or_equal' => __('glitter-audit_log::messages.validation.date_range'),
            'per_page.max' => __('glitter-audit_log::messages.validation.per_page_max'),
        ];
    }
}
