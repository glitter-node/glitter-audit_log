<?php

namespace Modules\Glitter\AuditLog\Tests\Feature\Admin;

use App\Enums\ActivityLogType;
use App\Enums\ExtensionOwnerType;
use App\Helpers\PermissionHelper;
use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Glitter\AuditLog\Providers\AuditLogServiceProvider;
use Tests\TestCase;

class AuditExplorerControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/modules/glitter-audit_log/admin/audits';

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerBundledAutoload();
        $this->app->register(AuditLogServiceProvider::class);
        Route::prefix('api/modules/glitter-audit_log')
            ->name('api.modules.glitter-audit_log.')
            ->middleware('api')
            ->group(dirname(__DIR__, 3).'/src/routes/api.php');
        $this->clearPermissionCache();
    }

    protected function tearDown(): void
    {
        $this->clearPermissionCache();
        parent::tearDown();
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(self::ENDPOINT)->assertUnauthorized();
    }

    public function test_requires_module_and_core_read_permissions(): void
    {
        $admin = $this->createAdmin(['glitter-audit_log.audits.read']);

        $this->actingAs($admin)->getJson(self::ENDPOINT)->assertForbidden();
    }

    public function test_rejects_invalid_filter(): void
    {
        $admin = $this->createAdmin($this->requiredPermissions());

        $this->actingAs($admin)
            ->getJson(self::ENDPOINT.'?source_type=invalid')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['source_type']);
    }

    public function test_returns_paginated_normalized_structure(): void
    {
        $admin = $this->createAdmin($this->requiredPermissions());
        $this->createLog($admin, [
            'action' => 'page.update',
            'description_key' => 'sirsoft-page::activity_log.description.page_update',
            'loggable_type' => 'Modules\\Sirsoft\\Page\\Models\\Page',
            'loggable_id' => 10,
            'properties' => ['extension_origin' => 'sirsoft-page'],
        ]);

        $this->actingAs($admin)
            ->getJson(self::ENDPOINT.'?per_page=10&source_identifier=sirsoft-page')
            ->assertOk()
            ->assertJsonPath('data.data.0.source.type', 'module')
            ->assertJsonPath('data.data.0.source.identifier', 'sirsoft-page')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [[
                        'id', 'type', 'action', 'actor', 'resource', 'source', 'ip',
                        'created_at', 'description', 'changes', 'bulk_changes', 'properties',
                    ]],
                    'pagination' => [
                        'current_page', 'per_page', 'from', 'to', 'has_more_pages',
                        'last_page', 'total',
                    ],
                ],
            ])
            ->assertJsonMissing(['user_agent']);
    }

    public function test_core_permission_self_scope_is_not_bypassed(): void
    {
        $admin = $this->createAdmin($this->requiredPermissions(), 'self');
        $other = User::factory()->create();
        $own = $this->createLog($admin, ['action' => 'audit.own']);
        $this->createLog($other, ['action' => 'audit.other']);

        $response = $this->actingAs($admin)->getJson(self::ENDPOINT);

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $own->id);
    }

    /** @return array<int, string> */
    private function requiredPermissions(): array
    {
        return ['glitter-audit_log.audits.read', 'core.activities.read'];
    }

    private function createAdmin(array $permissions, ?string $coreScope = null): User
    {
        $user = User::factory()->create();
        $adminRole = Role::firstOrCreate(
            ['identifier' => 'admin'],
            ['name' => ['ko' => '관리자', 'en' => 'Administrator'], 'is_active' => true],
        );
        $permissionRole = Role::create([
            'identifier' => 'audit_test_'.uniqid(),
            'name' => ['ko' => '감사 테스트', 'en' => 'Audit Test'],
            'is_active' => true,
        ]);
        $user->roles()->attach([$adminRole->id, $permissionRole->id]);

        foreach ($permissions as $identifier) {
            $isCore = $identifier === 'core.activities.read';
            $permission = Permission::firstOrCreate(
                ['identifier' => $identifier],
                [
                    'name' => ['ko' => $identifier, 'en' => $identifier],
                    'description' => ['ko' => $identifier, 'en' => $identifier],
                    'extension_type' => $isCore ? ExtensionOwnerType::Core : ExtensionOwnerType::Module,
                    'extension_identifier' => $isCore ? 'core' : 'glitter-audit_log',
                    'type' => 'admin',
                    'resource_route_key' => $isCore ? 'activityLog' : null,
                    'owner_key' => $isCore ? 'user_id' : null,
                ],
            );
            if ($isCore) {
                $permission->update(['resource_route_key' => 'activityLog', 'owner_key' => 'user_id']);
            }
            $permissionRole->permissions()->attach($permission->id, [
                'scope_type' => $isCore ? $coreScope : null,
            ]);
        }

        $this->clearPermissionCache();

        return $user->fresh();
    }

    private function createLog(User $actor, array $overrides = []): ActivityLog
    {
        return ActivityLog::create(array_merge([
            'log_type' => ActivityLogType::Admin,
            'action' => 'user.update',
            'description_key' => 'activity_log.description.user_update',
            'user_id' => $actor->id,
            'ip_address' => '127.0.0.1',
        ], $overrides));
    }

    private function clearPermissionCache(): void
    {
        $reflection = new \ReflectionClass(PermissionHelper::class);
        $property = $reflection->getProperty('permissionCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    private function registerBundledAutoload(): void
    {
        $base = dirname(__DIR__, 3);
        spl_autoload_register(function (string $class) use ($base): void {
            $prefix = 'Modules\\Glitter\\AuditLog\\';
            if (! str_starts_with($class, $prefix)) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $path = $base.'/src/'.$relative.'.php';
            if (is_file($path)) {
                require_once $path;
            }
        });
    }
}
