<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Providers;

use Illuminate\Foundation\Application;
use Modules\Glitter\AuditLog\Contracts\AuditCoverageServiceInterface;
use Modules\Glitter\AuditLog\Contracts\AuditEventDiscoveryInterface;
use Modules\Glitter\AuditLog\Contracts\AuditEventRegistryInterface;
use Modules\Glitter\AuditLog\Contracts\AuditQueryServiceInterface;
use Modules\Glitter\AuditLog\Contracts\AuditStructuralAnalyzerInterface;
use Modules\Glitter\AuditLog\Providers\AuditLogServiceProvider;
use Modules\Glitter\AuditLog\Repositories\AuditLogRepository;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditLogRepositoryInterface;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditObservationRepositoryInterface;
use Modules\Glitter\AuditLog\Services\AuditQueryService;
use PHPUnit\Framework\TestCase;

class AuditLogServiceProviderTest extends TestCase
{
    public function test_binds_public_query_and_repository_contracts(): void
    {
        $app = new Application(dirname(__DIR__, 5));
        (new AuditLogServiceProvider($app))->register();

        $this->assertTrue($app->bound(AuditQueryServiceInterface::class));
        $this->assertTrue($app->bound(AuditLogRepositoryInterface::class));
        $this->assertTrue($app->bound(AuditEventDiscoveryInterface::class));
        $this->assertTrue($app->bound(AuditEventRegistryInterface::class));
        $this->assertTrue($app->bound(AuditCoverageServiceInterface::class));
        $this->assertTrue($app->bound(AuditStructuralAnalyzerInterface::class));
        $this->assertTrue($app->bound(AuditObservationRepositoryInterface::class));
        $this->assertInstanceOf(AuditLogRepository::class, $app->make(AuditLogRepositoryInterface::class));
        $this->assertInstanceOf(AuditQueryService::class, $app->make(AuditQueryServiceInterface::class));
    }
}
