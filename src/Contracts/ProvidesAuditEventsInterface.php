<?php

namespace Modules\Glitter\AuditLog\Contracts;

interface ProvidesAuditEventsInterface
{
    /** @return list<array<string, string>> */
    public function getAuditEvents(): array;
}
