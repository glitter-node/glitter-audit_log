<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Services;

use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditLogRepositoryInterface;
use Modules\Glitter\AuditLog\Services\AuditQueryService;
use Modules\Glitter\AuditLog\Support\AuditSourceResolver;
use PHPUnit\Framework\TestCase;

class AuditQueryServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_paginate_forwards_filters_without_adding_writer_behavior(): void
    {
        $filters = ['source_identifier' => 'sirsoft-page', 'resource_id' => 10];
        $log = new ActivityLog([
            'loggable_type' => 'Modules\\Sirsoft\\Page\\Models\\Page',
        ]);
        $paginator = new LengthAwarePaginator([$log], 1, 20);
        $repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $repository->shouldReceive('paginate')->once()->with($filters)->andReturn($paginator);

        $service = new AuditQueryService($repository, new AuditSourceResolver);

        $result = $service->paginate($filters);

        $this->assertSame($paginator, $result);
        $this->assertSame(
            ['type' => 'module', 'identifier' => 'sirsoft-page'],
            $result->items()[0]->getAttribute('audit_source'),
        );
    }
}
