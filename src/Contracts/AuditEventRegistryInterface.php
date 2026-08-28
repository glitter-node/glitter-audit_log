<?php

namespace Modules\Glitter\AuditLog\Contracts;

use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;

interface AuditEventRegistryInterface
{
    /** @return list<AuditEventDefinition> */
    public function all(): array;

    public function find(string $key): ?AuditEventDefinition;

    /** @return list<AuditEventDefinition> */
    public function forSource(string $type, string $identifier): array;
}
