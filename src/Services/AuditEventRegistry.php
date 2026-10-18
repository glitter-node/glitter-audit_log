<?php

namespace Modules\Glitter\AuditLog\Services;

use LogicException;
use Modules\Glitter\AuditLog\Contracts\AuditEventDiscoveryInterface;
use Modules\Glitter\AuditLog\Contracts\AuditEventRegistryInterface;
use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;
use Modules\Glitter\AuditLog\Support\AuditEventDefinitionFactory;

class AuditEventRegistry implements AuditEventRegistryInterface
{
    /** @var array<string, AuditEventDefinition>|null */
    private ?array $definitions = null;

    public function __construct(
        private readonly AuditEventDiscoveryInterface $discovery,
        private readonly AuditEventDefinitionFactory $factory,
    ) {}

    public function all(): array
    {
        return array_values($this->loaded());
    }

    public function find(string $key): ?AuditEventDefinition
    {
        return $this->loaded()[$key] ?? null;
    }

    public function forSource(string $type, string $identifier): array
    {
        return array_values(array_filter(
            $this->loaded(),
            fn (AuditEventDefinition $definition): bool => $definition->sourceType === $type
                && $definition->sourceIdentifier === $identifier,
        ));
    }

    /** @return array<string, AuditEventDefinition> */
    private function loaded(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $this->definitions = [];
        foreach ($this->discovery->definitions() as $raw) {
            $definition = $this->factory->make($raw);
            if (isset($this->definitions[$definition->key])) {
                throw new LogicException("Duplicate audit event definition key: {$definition->key}");
            }
            $this->definitions[$definition->key] = $definition;
        }

        ksort($this->definitions);

        return $this->definitions;
    }
}
