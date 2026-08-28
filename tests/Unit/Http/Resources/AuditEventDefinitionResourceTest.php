<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Http\Resources;

use Illuminate\Http\Request;
use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;
use Modules\Glitter\AuditLog\Http\Resources\AuditEventDefinitionResource;
use PHPUnit\Framework\TestCase;

class AuditEventDefinitionResourceTest extends TestCase
{
    public function test_transforms_definition_without_database(): void
    {
        $definition = new AuditEventDefinition(
            'sample.created', 'module', 'sample', 'content', 'info',
            'sample::activity.created', 'created',
        );

        $result = (new AuditEventDefinitionResource($definition))->toArray(Request::create('/'));

        self::assertSame(['type' => 'module', 'identifier' => 'sample'], $result['source']);
        self::assertSame('sample::activity.created', $result['description']['key']);
        self::assertSame('created', $result['expected_action']);
    }
}
