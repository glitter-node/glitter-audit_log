<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Modules\Glitter\AuditLog\Http\Resources\AuditLogResource;
use PHPUnit\Framework\TestCase;

class AuditLogResourceTest extends TestCase
{
    public function test_transforms_core_resource_without_user_agent_or_database(): void
    {
        $log = new ActivityLog;
        $log->setRawAttributes([
            'log_type' => 'admin',
            'action' => 'user.update',
            'description_key' => 'activity_log.description.user_update',
            'loggable_type' => 'App\\Models\\User',
            'loggable_id' => 7,
            'properties' => json_encode(['safe' => true]),
            'changes' => json_encode([[
                'field' => 'name',
                'label_key' => 'name',
                'old' => 'before',
                'new' => 'after',
                'type' => 'text',
            ]]),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'must-not-be-exposed',
            'created_at' => '2026-08-29 00:00:00',
        ], true);
        $log->setDateFormat('Y-m-d H:i:s');
        $log->setRelation('user', null);
        $log->setAttribute('audit_source', ['type' => 'core', 'identifier' => 'core']);

        $data = (new AuditLogResource($log))->toArray(Request::create('/audits', 'GET'));

        $this->assertSame('admin', $data['type']['value']);
        $this->assertSame('user.update', $data['action']['value']);
        $this->assertSame(['type' => 'core', 'identifier' => 'core'], $data['source']);
        $this->assertSame(7, $data['resource']['id']);
        $this->assertSame('before', $data['changes'][0]['old']);
        $this->assertSame(['safe' => true], $data['properties']);
        $this->assertArrayNotHasKey('user_agent', $data);
    }
}
