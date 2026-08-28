<?php

namespace Modules\Glitter\AuditLog\Repositories\Contracts;

use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;

interface AuditObservationRepositoryInterface
{
    /** @param list<AuditEventDefinition> $definitions
     *  @return array<string, array{observed: bool, count: int, first_seen_at: string|null, last_seen_at: string|null}>
     */
    public function aggregate(array $definitions): array;
}
