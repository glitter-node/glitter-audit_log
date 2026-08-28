<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Http\Resources;

use Illuminate\Http\Request;
use Modules\Glitter\AuditLog\DTO\AuditCoverageResult;
use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;
use Modules\Glitter\AuditLog\Http\Resources\AuditCoverageResource;
use PHPUnit\Framework\TestCase;

class AuditCoverageResourceTest extends TestCase
{
    public function test_transforms_event_structural_observation_and_diagnostics(): void
    {
        $result = new AuditCoverageResult(
            new AuditEventDefinition('fixture.update', 'module', 'fixture', 'content', 'info', 'activity_log.description.user_update', 'fixture.update'),
            'partial',
            [['key' => 'expected_action_mapping', 'status' => 'failed', 'evidence' => null]],
            ['observed' => false, 'count' => 0, 'first_seen_at' => null, 'last_seen_at' => null],
            ['expected_action_not_mapped', 'no_observation'],
        );

        $data = (new AuditCoverageResource($result))->toArray(Request::create('/'));

        self::assertSame('fixture.update', $data['event']['key']);
        self::assertSame('partial', $data['structural']['status']);
        self::assertFalse($data['observation']['observed']);
        self::assertSame(['expected_action_not_mapped', 'no_observation'], $data['diagnostics']);
    }
}
