<?php

namespace Modules\Glitter\AuditLog\Support;

use InvalidArgumentException;
use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;

class AuditEventDefinitionFactory
{
    private const SOURCE_TYPES = ['core', 'module', 'plugin', 'template'];

    private const SEVERITIES = ['info', 'notice', 'warning', 'critical'];

    /** @param array<string, mixed> $definition */
    public function make(array $definition): AuditEventDefinition
    {
        foreach (['key', 'source_type', 'source_identifier', 'category', 'severity', 'description_key', 'expected_action'] as $field) {
            if (! isset($definition[$field]) || ! is_string($definition[$field]) || trim($definition[$field]) === '') {
                throw new InvalidArgumentException("Invalid audit event definition field: {$field}");
            }
        }

        if (! preg_match('/^[a-z0-9][a-z0-9._-]*$/', $definition['key'])) {
            throw new InvalidArgumentException('Invalid audit event definition key.');
        }

        if (! in_array($definition['source_type'], self::SOURCE_TYPES, true)) {
            throw new InvalidArgumentException('Invalid audit event source type.');
        }

        if (! in_array($definition['severity'], self::SEVERITIES, true)) {
            throw new InvalidArgumentException('Invalid audit event severity.');
        }

        return new AuditEventDefinition(
            key: $definition['key'],
            sourceType: $definition['source_type'],
            sourceIdentifier: $definition['source_identifier'],
            category: $definition['category'],
            severity: $definition['severity'],
            descriptionKey: $definition['description_key'],
            expectedAction: $definition['expected_action'],
        );
    }
}
