<?php

namespace Modules\Glitter\AuditLog\Support;

use App\Extension\ExtensionManager;
use App\Models\ActivityLog;
use App\Models\Module;
use App\Models\Plugin;
use App\Models\Template;

class AuditSourceResolver
{
    /** @var array<string, string|null> */
    private array $originTypes = [];

    /**
     * @return array{type: string, identifier: string|null}
     */
    public function resolve(ActivityLog $log): array
    {
        $properties = is_array($log->properties) ? $log->properties : [];
        $origin = $properties['extension_origin'] ?? null;

        if (is_string($origin) && $origin !== '') {
            $type = $this->resolveOriginType($origin, $log->loggable_type);
            if ($type !== null) {
                return ['type' => $type, 'identifier' => $origin];
            }

            return ['type' => 'unknown', 'identifier' => null];
        }

        $loggableType = ltrim((string) $log->loggable_type, '\\');
        if ($loggableType !== '') {
            if (str_starts_with($loggableType, 'Modules\\')) {
                return $this->extensionSource('module', $loggableType);
            }

            if (str_starts_with($loggableType, 'Plugins\\')) {
                return $this->extensionSource('plugin', $loggableType);
            }

            if (str_starts_with($loggableType, 'App\\')) {
                return ['type' => 'core', 'identifier' => 'core'];
            }
        }

        if (is_string($log->description_key) && str_starts_with($log->description_key, 'activity_log.')) {
            return ['type' => 'core', 'identifier' => 'core'];
        }

        return ['type' => 'unknown', 'identifier' => null];
    }

    /** @return array{type: string, identifier: string|null} */
    private function extensionSource(string $type, string $fqcn): array
    {
        $identifier = ExtensionManager::resolveExtensionByFqcn($fqcn);

        return $identifier !== null
            ? ['type' => $type, 'identifier' => $identifier]
            : ['type' => 'unknown', 'identifier' => null];
    }

    private function resolveOriginType(string $origin, ?string $loggableType): ?string
    {
        $fqcn = ltrim((string) $loggableType, '\\');
        if (str_starts_with($fqcn, 'Modules\\')) {
            return 'module';
        }
        if (str_starts_with($fqcn, 'Plugins\\')) {
            return 'plugin';
        }

        if (array_key_exists($origin, $this->originTypes)) {
            return $this->originTypes[$origin];
        }

        return $this->originTypes[$origin] = match (true) {
            Module::where('identifier', $origin)->exists() => 'module',
            Plugin::where('identifier', $origin)->exists() => 'plugin',
            Template::where('identifier', $origin)->exists() => 'template',
            default => null,
        };
    }
}
