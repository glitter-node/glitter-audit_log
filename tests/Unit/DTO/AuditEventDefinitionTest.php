<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\DTO;

use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;
use PHPUnit\Framework\TestCase;

class AuditEventDefinitionTest extends TestCase
{
    public function test_it_is_an_immutable_serializable_value(): void
    {
        $definition = new AuditEventDefinition(
            'sample.item.created', 'module', 'sample', 'content', 'info',
            'sample::activity.item_created', 'created',
        );

        self::assertTrue((new \ReflectionClass($definition))->isReadOnly());
        self::assertSame('sample.item.created', $definition->toArray()['key']);
        self::assertSame('sample', $definition->toArray()['source_identifier']);
    }
}
