<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Support;

use App\Models\ActivityLog;
use Modules\Glitter\AuditLog\Support\AuditSourceResolver;
use PHPUnit\Framework\TestCase;

class AuditSourceResolverTest extends TestCase
{
    private AuditSourceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new AuditSourceResolver;
    }

    public function test_resolves_core_from_core_model(): void
    {
        $source = $this->resolver->resolve(new ActivityLog(['loggable_type' => 'App\\Models\\User']));

        $this->assertSame(['type' => 'core', 'identifier' => 'core'], $source);
    }

    public function test_resolves_module_from_loggable_fqcn(): void
    {
        $source = $this->resolver->resolve(new ActivityLog([
            'loggable_type' => 'Modules\\Sirsoft\\Page\\Models\\Page',
        ]));

        $this->assertSame(['type' => 'module', 'identifier' => 'sirsoft-page'], $source);
    }

    public function test_extension_origin_takes_priority_and_uses_fqcn_type(): void
    {
        $source = $this->resolver->resolve(new ActivityLog([
            'loggable_type' => 'Modules\\Sirsoft\\Board\\Models\\Post',
            'properties' => ['extension_origin' => 'sirsoft-board'],
        ]));

        $this->assertSame(['type' => 'module', 'identifier' => 'sirsoft-board'], $source);
    }

    public function test_returns_unknown_without_origin_or_recognized_fqcn(): void
    {
        $source = $this->resolver->resolve(new ActivityLog([
            'action' => 'legacy.event',
            'description_key' => 'legacy.description',
        ]));

        $this->assertSame(['type' => 'unknown', 'identifier' => null], $source);
    }
}
