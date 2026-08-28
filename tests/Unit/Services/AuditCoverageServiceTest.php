<?php

namespace Modules\Glitter\AuditLog\Tests\Unit\Services;

use Modules\Glitter\AuditLog\Contracts\AuditEventRegistryInterface;
use Modules\Glitter\AuditLog\Contracts\AuditStructuralAnalyzerInterface;
use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditObservationRepositoryInterface;
use Modules\Glitter\AuditLog\Services\AuditCoverageService;
use PHPUnit\Framework\TestCase;

class AuditCoverageServiceTest extends TestCase
{
    public function test_separates_all_structural_states_from_observation_and_aggregates_once(): void
    {
        $definitions = array_map(
            fn (string $status): AuditEventDefinition => $this->definition($status),
            ['covered', 'partial', 'uncovered', 'unknown'],
        );
        $registry = $this->registry($definitions);
        $analyzer = new class implements AuditStructuralAnalyzerInterface
        {
            public function analyze(AuditEventDefinition $definition): array
            {
                return [
                    'status' => $definition->category,
                    'checks' => [['key' => 'fixture', 'status' => 'passed', 'evidence' => null]],
                    'diagnostics' => $definition->category === 'covered' ? [] : ['structural_'.$definition->category],
                ];
            }
        };
        $observations = new class implements AuditObservationRepositoryInterface
        {
            public int $calls = 0;

            public function aggregate(array $definitions): array
            {
                $this->calls++;

                return [
                    'fixture.covered' => ['observed' => true, 'count' => 3, 'first_seen_at' => '2026-01-01', 'last_seen_at' => '2026-01-02'],
                ];
            }
        };

        $results = (new AuditCoverageService($registry, $analyzer, $observations))->all();

        self::assertSame(1, $observations->calls);
        self::assertSame(['covered', 'partial', 'uncovered', 'unknown'], array_column($results, 'structuralStatus'));
        self::assertTrue($results[0]->observation['observed']);
        self::assertFalse($results[1]->observation['observed']);
        self::assertSame('partial', $results[1]->structuralStatus);
        self::assertContains('no_observation', $results[1]->diagnostics);
        self::assertSame('covered', $results[0]->structuralStatus);
    }

    public function test_filters_by_source_status_observation_and_event_key(): void
    {
        $definitions = [$this->definition('covered'), $this->definition('partial', 'other-module')];
        $service = new AuditCoverageService(
            $this->registry($definitions),
            new class implements AuditStructuralAnalyzerInterface
            {
                public function analyze(AuditEventDefinition $definition): array
                {
                    return ['status' => $definition->category, 'checks' => [], 'diagnostics' => []];
                }
            },
            new class implements AuditObservationRepositoryInterface
            {
                public function aggregate(array $definitions): array
                {
                    return ['fixture.covered' => ['observed' => true, 'count' => 1, 'first_seen_at' => null, 'last_seen_at' => null]];
                }
            },
        );

        self::assertCount(1, $service->forSource('module', 'other-module'));
        self::assertSame('fixture.covered', $service->forEvent('fixture.covered')?->event->key);
        self::assertCount(1, $service->all(['structural_status' => 'covered', 'observed' => true]));
        self::assertCount(0, $service->all(['structural_status' => 'uncovered']));
    }

    private function definition(string $status, string $source = 'fixture-module'): AuditEventDefinition
    {
        return new AuditEventDefinition(
            key: 'fixture.'.$status,
            sourceType: 'module',
            sourceIdentifier: $source,
            category: $status,
            severity: 'info',
            descriptionKey: 'activity_log.description.user_update',
            expectedAction: 'fixture.update',
        );
    }

    private function registry(array $definitions): AuditEventRegistryInterface
    {
        return new class($definitions) implements AuditEventRegistryInterface
        {
            public function __construct(private readonly array $definitions) {}

            public function all(): array
            {
                return $this->definitions;
            }

            public function find(string $key): ?AuditEventDefinition
            {
                foreach ($this->definitions as $definition) {
                    if ($definition->key === $key) {
                        return $definition;
                    }
                }

                return null;
            }

            public function forSource(string $type, string $identifier): array
            {
                return array_values(array_filter($this->definitions, fn ($definition) => $definition->sourceType === $type && $definition->sourceIdentifier === $identifier));
            }
        };
    }
}
