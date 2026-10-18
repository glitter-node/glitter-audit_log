<?php

namespace Modules\Glitter\AuditLog\Contracts;

use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;

interface AuditStructuralAnalyzerInterface
{
    /** @return array{status: string, checks: list<array{key: string, status: string, evidence: string|null}>, diagnostics: list<string>} */
    public function analyze(AuditEventDefinition $definition): array;
}
