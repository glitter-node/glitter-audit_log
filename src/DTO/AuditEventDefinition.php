<?php

namespace Modules\Glitter\AuditLog\DTO;

final readonly class AuditEventDefinition
{
    public function __construct(
        public string $key,
        public string $sourceType,
        public string $sourceIdentifier,
        public string $category,
        public string $severity,
        public string $descriptionKey,
        public string $expectedAction,
    ) {}

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'source_type' => $this->sourceType,
            'source_identifier' => $this->sourceIdentifier,
            'category' => $this->category,
            'severity' => $this->severity,
            'description_key' => $this->descriptionKey,
            'expected_action' => $this->expectedAction,
        ];
    }
}
