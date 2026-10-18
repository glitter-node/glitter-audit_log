<?php

namespace Modules\Glitter\AuditLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditCoverageIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'source_type' => ['nullable', Rule::in(['core', 'module', 'plugin', 'template'])],
            'source_identifier' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'structural_status' => ['nullable', Rule::in(['covered', 'partial', 'uncovered', 'unknown'])],
            'observed' => ['nullable', 'boolean'],
            'event_key' => ['nullable', 'string', 'max:150', 'regex:/^[a-z0-9][a-z0-9._-]*$/'],
        ];
    }
}
