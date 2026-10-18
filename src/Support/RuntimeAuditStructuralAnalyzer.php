<?php

namespace Modules\Glitter\AuditLog\Support;

use App\ActivityLog\Traits\ResolvesActivityLogType;
use App\Extension\HookCacheManager;
use Illuminate\Support\Facades\Lang;
use Modules\Glitter\AuditLog\Contracts\AuditStructuralAnalyzerInterface;
use Modules\Glitter\AuditLog\DTO\AuditEventDefinition;

class RuntimeAuditStructuralAnalyzer implements AuditStructuralAnalyzerInterface
{
    public function __construct(private readonly HookCacheManager $hooks) {}

    public function analyze(AuditEventDefinition $definition): array
    {
        $entries = $this->listenerEntries($definition);
        if ($entries === null) {
            return [
                'status' => 'unknown',
                'checks' => [$this->check('source_activity_listener', 'unknown')],
                'diagnostics' => ['structural_unknown'],
            ];
        }

        $activityEntries = array_values(array_filter($entries, function (array $entry): bool {
            $listener = $entry['listener'] ?? null;

            return is_string($listener) && class_exists($listener)
                && in_array(ResolvesActivityLogType::class, class_uses_recursive($listener), true);
        }));
        if ($activityEntries === []) {
            return [
                'status' => 'uncovered',
                'checks' => [$this->check('source_activity_listener', 'failed')],
                'diagnostics' => ['source_listener_missing'],
            ];
        }

        $listeners = implode(', ', array_column($activityEntries, 'listener'));
        $checks = [$this->check('source_activity_listener', 'passed', $listeners)];
        $actionMapped = $this->actionMapped($definition->expectedAction, $activityEntries);
        $checks[] = $this->check('expected_action_mapping', $actionMapped ? 'passed' : 'failed');
        $translation = Lang::has($definition->descriptionKey);
        $checks[] = $this->check('description_translation', $translation ? 'passed' : 'failed', $definition->descriptionKey);

        $diagnostics = [];
        if (! $actionMapped) {
            $diagnostics[] = 'expected_action_not_mapped';
        }
        if (! $translation) {
            $diagnostics[] = 'translation_missing';
        }

        return [
            'status' => $diagnostics === [] ? 'covered' : 'partial',
            'checks' => $checks,
            'diagnostics' => $diagnostics,
        ];
    }

    private function listenerEntries(AuditEventDefinition $definition): ?array
    {
        $cache = $this->hooks->read();
        if ($cache === null) {
            return null;
        }

        return match ($definition->sourceType) {
            'core' => $cache['core'],
            'module' => $cache['modules'][$definition->sourceIdentifier] ?? [],
            'plugin' => $cache['plugins'][$definition->sourceIdentifier] ?? [],
            'template' => [],
            default => null,
        };
    }

    private function actionMapped(string $action, array $entries): bool
    {
        $segment = last(explode('.', $action));
        foreach ($entries as $entry) {
            foreach (array_keys($entry['hooks'] ?? []) as $hook) {
                if (str_ends_with($hook, ".after_{$segment}") || str_ends_with($hook, ".{$segment}")) {
                    return true;
                }
            }
        }

        return false;
    }

    private function check(string $key, string $status, ?string $evidence = null): array
    {
        return ['key' => $key, 'status' => $status, 'evidence' => $evidence];
    }
}
