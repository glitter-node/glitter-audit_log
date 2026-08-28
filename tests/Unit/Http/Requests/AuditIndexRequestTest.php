<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Http\Requests;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;
use Modules\Glitter\AuditLog\Http\Requests\AuditIndexRequest;
use PHPUnit\Framework\TestCase;

class AuditIndexRequestTest extends TestCase
{
    public function test_valid_filters_pass_without_database(): void
    {
        $validator = $this->validator([
            'log_type' => ['admin', 'system'],
            'action' => 'page.update',
            'actor' => 'actor@example.com',
            'resource_type' => 'Modules\\Sirsoft\\Page\\Models\\Page',
            'resource_id' => 10,
            'source_type' => 'module',
            'source_identifier' => 'sirsoft-page',
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-29',
            'search' => 'page',
            'per_page' => 20,
            'page' => 1,
        ]);

        $this->assertTrue($validator->passes(), json_encode($validator->errors()->toArray()));
    }

    public function test_invalid_source_and_page_size_fail(): void
    {
        $validator = $this->validator([
            'source_type' => 'invalid',
            'source_identifier' => '../invalid',
            'per_page' => 101,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(['source_type', 'source_identifier', 'per_page'], array_keys($validator->errors()->toArray()));
    }

    private function validator(array $data): Validator
    {
        $request = new AuditIndexRequest;
        $factory = new Factory(new Translator(new ArrayLoader, 'en'));

        return $factory->make($data, $request->rules());
    }
}
