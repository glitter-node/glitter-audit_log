<?php

namespace Modules\Glitter\AuditLog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Glitter\AuditLog\Contracts\AuditQueryServiceInterface;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditLogRepositoryInterface;
use Modules\Glitter\AuditLog\Support\AuditSourceResolver;

class AuditQueryService implements AuditQueryServiceInterface
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly AuditSourceResolver $sourceResolver,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $logs = $this->repository->paginate($filters);

        // DB 원장에는 source 컬럼을 추가하지 않고 현재 페이지에만 read-model 속성을 붙인다.
        $logs->getCollection()->each(function ($log): void {
            $log->setAttribute('audit_source', $this->sourceResolver->resolve($log));
        });

        return $logs;
    }
}
