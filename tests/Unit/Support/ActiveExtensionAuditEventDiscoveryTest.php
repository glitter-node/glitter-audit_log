<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Support;

use App\Contracts\Extension\ModuleManagerInterface;
use App\Contracts\Extension\PluginManagerInterface;
use App\Contracts\Extension\TemplateManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Glitter\AuditLog\Support\ActiveExtensionAuditEventDiscovery;
use PHPUnit\Framework\TestCase;

class ActiveExtensionAuditEventDiscoveryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_discovers_only_returned_active_extensions_and_skips_optional_absence(): void
    {
        $active = new class
        {
            public function getIdentifier(): string
            {
                return 'sample-module';
            }

            public function getAuditEvents(): array
            {
                return [[
                    'key' => 'sample.created', 'source_type' => 'module',
                    'source_identifier' => 'sample-module', 'category' => 'content',
                    'severity' => 'info', 'description_key' => 'sample::created',
                    'expected_action' => 'created',
                ]];
            }
        };
        $withoutDeclaration = new class
        {
            public function getIdentifier(): string
            {
                return 'no-audit-module';
            }
        };

        $modules = Mockery::mock(ModuleManagerInterface::class);
        $modules->shouldReceive('getActiveModules')->once()->andReturn([$active, $withoutDeclaration]);
        $plugins = Mockery::mock(PluginManagerInterface::class);
        $plugins->shouldReceive('getActivePlugins')->once()->andReturn([]);
        $templates = Mockery::mock(TemplateManagerInterface::class);
        $templates->shouldReceive('getActiveTemplate')->with('admin')->once()->andReturn([
            'identifier' => 'sample-admin',
            'audit_events' => [[
                'key' => 'sample.template.loaded', 'source_type' => 'template',
                'source_identifier' => 'sample-admin', 'category' => 'lifecycle',
                'severity' => 'notice', 'description_key' => 'sample.loaded',
                'expected_action' => 'loaded',
            ]],
        ]);
        $templates->shouldReceive('getActiveTemplate')->with('user')->once()->andReturn(null);

        $definitions = (new ActiveExtensionAuditEventDiscovery($modules, $plugins, $templates))->definitions();

        self::assertSame(['sample.created', 'sample.template.loaded'], array_column($definitions, 'key'));
        self::assertNotContains('inactive-module', array_column($definitions, 'source_identifier'));
    }
}
