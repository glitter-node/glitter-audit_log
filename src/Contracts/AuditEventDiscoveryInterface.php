<?php

namespace Modules\Glitter\AuditLog\Contracts;

interface AuditEventDiscoveryInterface
{
    /** @return list<array<string, mixed>> */
    public function definitions(): array;
}
