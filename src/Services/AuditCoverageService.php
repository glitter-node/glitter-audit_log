<?php

namespace Modules\Glitter\AuditLog\Services;

use Modules\Glitter\AuditLog\Contracts\AuditCoverageServiceInterface;
use Modules\Glitter\AuditLog\Contracts\AuditEventRegistryInterface;
use Modules\Glitter\AuditLog\Contracts\AuditStructuralAnalyzerInterface;
use Modules\Glitter\AuditLog\DTO\AuditCoverageResult;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditObservationRepositoryInterface;

class AuditCoverageService implements AuditCoverageServiceInterface
{
    public function __construct(
        private readonly AuditEventRegistryInterface $registry,
        private readonly AuditStructuralAnalyzerInterface $structuralAnalyzer,
        private readonly AuditObservationRepositoryInterface $observations,
    ) {}

    public function all(array $filters = []): array
    {
        $definitions = $this->registry->all();
        $observationMap = $this->observations->aggregate($definitions);
        $results = array_map(function ($definition) use ($observationMap): AuditCoverageResult {
            $structural = $this->structuralAnalyzer->analyze($definition);
            $observation = $observationMap[$definition->key]
                ?? ['observed' => false, 'count' => 0, 'first_seen_at' => null, 'last_seen_at' => null];
            $diagnostics = $structural['diagnostics'];
            if (! $observation['observed']) {
                $diagnostics[] = 'no_observation';
            }

            return new AuditCoverageResult(
                $definition,
                $structural['status'],
                $structural['checks'],
                $observation,
                array_values(array_unique($diagnostics)),
            );
        }, $definitions);

        return array_values(array_filter($results, fn (AuditCoverageResult $result): bool => $this->matches($result, $filters)));
    }

    public function forSource(string $type, string $identifier): array
    {
        return $this->all(['source_type' => $type, 'source_identifier' => $identifier]);
    }

    public function forEvent(string $key): ?AuditCoverageResult
    {
        return $this->all(['event_key' => $key])[0] ?? null;
    }

    private function matches(AuditCoverageResult $result, array $filters): bool
    {
        return (! isset($filters['source_type']) || $result->event->sourceType === $filters['source_type'])
            && (! isset($filters['source_identifier']) || $result->event->sourceIdentifier === $filters['source_identifier'])
            && (! isset($filters['structural_status']) || $result->structuralStatus === $filters['structural_status'])
            && (! isset($filters['observed']) || $result->observation['observed'] === $filters['observed'])
            && (! isset($filters['event_key']) || $result->event->key === $filters['event_key']);
    }
}
