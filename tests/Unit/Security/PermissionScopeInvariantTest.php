<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Security;

use Modules\Glitter\AuditLog\Contracts\AuditQueryServiceInterface;
use Modules\Glitter\AuditLog\Http\Controllers\Admin\AuditExplorerController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class PermissionScopeInvariantTest extends TestCase
{
    public function test_route_requires_module_and_core_read_permissions(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 3).'/src/routes/api.php');

        $this->assertStringContainsString('permission:admin,glitter-audit_log.audits.read', $routes);
        $this->assertStringContainsString('permission:admin,core.activities.read', $routes);
        $this->assertStringContainsString('admin/event-definitions', $routes);
        $this->assertSame(2, substr_count($routes, 'permission:admin,glitter-audit_log.audits.read'));
        $this->assertSame(2, substr_count($routes, 'permission:admin,core.activities.read'));
    }

    public function test_repository_applies_core_scope_before_filters(): void
    {
        $repository = file_get_contents(dirname(__DIR__, 3).'/src/Repositories/AuditLogRepository.php');
        $scope = "PermissionHelper::applyPermissionScope(\$query, 'core.activities.read')";

        $this->assertStringContainsString($scope, $repository);
        $this->assertLessThan(
            strpos($repository, '$this->applyFilters($query, $filters)'),
            strpos($repository, $scope),
        );
    }

    public function test_controller_depends_on_public_query_contract(): void
    {
        $constructor = new ReflectionMethod(AuditExplorerController::class, '__construct');
        $type = $constructor->getParameters()[0]->getType();

        $this->assertSame(AuditQueryServiceInterface::class, $type?->getName());
    }
}
