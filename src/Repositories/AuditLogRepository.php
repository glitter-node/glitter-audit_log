<?php

namespace Modules\Glitter\AuditLog\Repositories;

use App\Enums\ActivityLogType;
use App\Extension\ExtensionManager;
use App\Helpers\PermissionHelper;
use App\Helpers\TimezoneHelper;
use App\Models\ActivityLog;
use App\Models\Module;
use App\Models\Plugin;
use App\Models\Template;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditLogRepositoryInterface;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    private const SOURCE_TYPES = ['core', 'module', 'plugin', 'template', 'unknown'];

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = ActivityLog::query()->with('user:id,uuid,name,email');

        // 이 모듈의 read 권한은 코어 원장의 기존 self/role/global 범위를 확장할 수 없다.
        PermissionHelper::applyPermissionScope($query, 'core.activities.read');

        $this->applyFilters($query, $filters);

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['log_type'])) {
            $types = is_array($filters['log_type']) ? $filters['log_type'] : [$filters['log_type']];
            $query->whereIn('log_type', array_intersect($types, ActivityLogType::values()));
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['actor'])) {
            $actor = $filters['actor'];
            $query->whereHas('user', fn (Builder $userQuery) => $userQuery
                ->where('uuid', $actor)
                ->orWhere('email', $actor));
        }

        if (! empty($filters['resource_type'])) {
            $query->where('loggable_type', $filters['resource_type']);
        }

        if (isset($filters['resource_id'])) {
            $query->where('loggable_id', (int) $filters['resource_id']);
        }

        if (! empty($filters['source_type']) || ! empty($filters['source_identifier'])) {
            $this->applySourceFilter(
                $query,
                $filters['source_type'] ?? null,
                $filters['source_identifier'] ?? null,
            );
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', TimezoneHelper::fromSiteDateTime($filters['date_from']));
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', TimezoneHelper::fromSiteDateTime($filters['date_to']));
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $nested) use ($search) {
                $nested->where('action', 'like', "%{$search}%")
                    ->orWhere('description_key', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('loggable_type', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }
    }

    private function applySourceFilter(Builder $query, ?string $type, ?string $identifier): void
    {
        if ($identifier !== null) {
            $resolvedType = $type ?? $this->findSourceType($identifier);
            if ($resolvedType === null || ! in_array($resolvedType, self::SOURCE_TYPES, true)) {
                $query->whereRaw('1 = 0');

                return;
            }

            $this->whereSource($query, $resolvedType, $identifier);

            return;
        }

        if ($type === 'core') {
            $query->where(function (Builder $source) {
                $source->where('loggable_type', 'like', 'App\\\\%')
                    ->orWhere(function (Builder $description) {
                        $description->where('description_key', 'like', 'activity_log.%')
                            ->where(function (Builder $origin) {
                                $origin->whereNull('properties')
                                    ->orWhereRaw('JSON_VALID(properties) = 0')
                                    ->orWhereRaw("JSON_EXTRACT(properties, '$.extension_origin') IS NULL");
                            });
                    });
            });

            return;
        }

        if (in_array($type, ['module', 'plugin', 'template'], true)) {
            $model = match ($type) {
                'module' => Module::class,
                'plugin' => Plugin::class,
                default => Template::class,
            };
            $identifiers = $model::query()->pluck('identifier')->all();

            $query->where(function (Builder $source) use ($type, $identifiers) {
                if ($type === 'module') {
                    $source->where('loggable_type', 'like', 'Modules\\\\%');
                } elseif ($type === 'plugin') {
                    $source->where('loggable_type', 'like', 'Plugins\\\\%');
                }

                if ($identifiers !== []) {
                    $method = $type === 'template' ? 'whereIn' : 'orWhereIn';
                    $source->{$method}(self::originExpression(), $identifiers);
                }
            });

            return;
        }

        // unknown은 판별 가능한 core/module/plugin/template 조건의 여집합이다.
        if ($type === 'unknown') {
            $knownOrigins = array_merge(
                Module::query()->pluck('identifier')->all(),
                Plugin::query()->pluck('identifier')->all(),
                Template::query()->pluck('identifier')->all(),
            );
            $query->where(function (Builder $unknown) {
                $unknown->whereNull('loggable_type')
                    ->orWhere(function (Builder $fqcn) {
                        $fqcn->where('loggable_type', 'not like', 'App\\\\%')
                            ->where('loggable_type', 'not like', 'Modules\\\\%')
                            ->where('loggable_type', 'not like', 'Plugins\\\\%');
                    });
            })->where(function (Builder $origin) use ($knownOrigins) {
                $origin->whereNull('properties')
                    ->orWhereRaw('JSON_VALID(properties) = 0')
                    ->orWhereNull(self::originExpression());
                if ($knownOrigins !== []) {
                    $origin->orWhereNotIn(self::originExpression(), $knownOrigins);
                }
            })->where(function (Builder $description) {
                $description->whereNull('description_key')
                    ->orWhere('description_key', 'not like', 'activity_log.%');
            });
        }
    }

    private function whereSource(Builder $query, string $type, string $identifier): void
    {
        if ($type === 'core' && $identifier === 'core') {
            $this->applySourceFilter($query, 'core', null);

            return;
        }

        $query->where(function (Builder $source) use ($type, $identifier) {
            $source->where(self::originExpression(), $identifier);

            if (in_array($type, ['module', 'plugin'], true)) {
                $prefix = $type === 'module' ? 'Modules' : 'Plugins';
                $namespace = ExtensionManager::directoryToNamespace($identifier);
                $source->orWhere('loggable_type', 'like', $prefix.'\\'.$namespace.'\\%');
            }
        });
    }

    private function findSourceType(string $identifier): ?string
    {
        if ($identifier === 'core') {
            return 'core';
        }
        if (Module::where('identifier', $identifier)->exists()) {
            return 'module';
        }
        if (Plugin::where('identifier', $identifier)->exists()) {
            return 'plugin';
        }
        if (Template::where('identifier', $identifier)->exists()) {
            return 'template';
        }

        return null;
    }

    private static function originExpression(): Expression
    {
        return DB::raw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.extension_origin'))");
    }
}
