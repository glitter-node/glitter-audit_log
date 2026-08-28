<?php

namespace Modules\Glitter\AuditLog\DTO;

final readonly class AuditCoverageResult
{
    public function __construct(
        public AuditEventDefinition $event,
        public string $structuralStatus,
        public array $structuralChecks,
        public array $observation,
        public array $diagnostics,
    ) {}

    public function toArray(): array
    {
        return [
            'event' => $this->event->toArray(),
            'structural' => ['status' => $this->structuralStatus, 'checks' => $this->structuralChecks],
            'observation' => $this->observation,
            'diagnostics' => $this->diagnostics,
        ];
    }
}
