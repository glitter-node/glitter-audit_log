<?php

namespace Modules\Glitter\AuditLog\Tests\Unit;

use Modules\Glitter\AuditLog\Module;
use PHPUnit\Framework\TestCase;

class ModuleAdminMenuTest extends TestCase
{
    public function test_admin_menu_uses_module_read_permission_and_audit_route(): void
    {
        $menu = (new Module)->getAdminMenus()[0];

        self::assertSame('/admin/modules/glitter-audit_log/audits', $menu['url']);
        self::assertSame('glitter-audit_log.audits.read', $menu['permission']);
        self::assertSame('glitter-audit-log', $menu['slug']);
        self::assertSame('/admin/modules/glitter-audit_log/coverage', $menu['children'][1]['url']);
        self::assertSame('glitter-audit_log.audits.read', $menu['children'][1]['permission']);
    }
}
