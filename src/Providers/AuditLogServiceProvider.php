<?php

namespace Modules\Glitter\AuditLog\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Glitter\AuditLog\Contracts\AuditEventDiscoveryInterface;
use Modules\Glitter\AuditLog\Contracts\AuditEventRegistryInterface;
use Modules\Glitter\AuditLog\Contracts\AuditCoverageServiceInterface;
use Modules\Glitter\AuditLog\Contracts\AuditQueryServiceInterface;
use Modules\Glitter\AuditLog\Contracts\AuditStructuralAnalyzerInterface;
use Modules\Glitter\AuditLog\Repositories\AuditObservationRepository;
use Modules\Glitter\AuditLog\Repositories\AuditLogRepository;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditLogRepositoryInterface;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditObservationRepositoryInterface;
use Modules\Glitter\AuditLog\Services\AuditCoverageService;
use Modules\Glitter\AuditLog\Services\AuditEventRegistry;
use Modules\Glitter\AuditLog\Services\AuditQueryService;
use Modules\Glitter\AuditLog\Support\ActiveExtensionAuditEventDiscovery;
use Modules\Glitter\AuditLog\Support\RuntimeAuditStructuralAnalyzer;

class AuditLogServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'glitter-audit_log';

    protected array $repositories = [
        AuditLogRepositoryInterface::class => AuditLogRepository::class,
        AuditObservationRepositoryInterface::class => AuditObservationRepository::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(AuditQueryServiceInterface::class, AuditQueryService::class);
        $this->app->bind(AuditEventDiscoveryInterface::class, ActiveExtensionAuditEventDiscovery::class);
        $this->app->singleton(AuditEventRegistryInterface::class, AuditEventRegistry::class);
        $this->app->bind(AuditStructuralAnalyzerInterface::class, RuntimeAuditStructuralAnalyzer::class);
        $this->app->bind(AuditCoverageServiceInterface::class, AuditCoverageService::class);
    }
}
