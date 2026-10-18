<?php

namespace Modules\Glitter\AuditLog\Contracts;

use Modules\Glitter\AuditLog\DTO\AuditCoverageResult;

interface AuditCoverageServiceInterface
{
    /** @return list<AuditCoverageResult> */
    public function all(array $filters = []): array;

    /** @return list<AuditCoverageResult> */
    public function forSource(string $type, string $identifier): array;

    public function forEvent(string $key): ?AuditCoverageResult;
}
