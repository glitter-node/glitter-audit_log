<?php

namespace Modules\Glitter\AuditLog\Repositories;

use App\Helpers\PermissionHelper;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Modules\Glitter\AuditLog\Repositories\Contracts\AuditObservationRepositoryInterface;
use Modules\Glitter\AuditLog\Support\AuditSourceResolver;

class AuditObservationRepository implements AuditObservationRepositoryInterface
{
    public function __construct(private readonly AuditSourceResolver $sourceResolver) {}

    public function aggregate(array $definitions): array
    {
        $empty = ['observed' => false, 'count' => 0, 'first_seen_at' => null, 'last_seen_at' => null];
        $result = array_fill_keys(array_map(fn ($definition) => $definition->key, $definitions), $empty);
        if ($definitions === []) {
            return $result;
        }

        $query = ActivityLog::query();
        PermissionHelper::applyPermissionScope($query, 'core.activities.read');
        $query->whereIn('action', array_values(array_unique(array_map(fn ($definition) => $definition->expectedAction, $definitions))))
            ->whereIn('description_key', array_values(array_unique(array_map(fn ($definition) => $definition->descriptionKey, $definitions))));

        $rows = $query->select([
            'action', 'description_key', 'loggable_type',
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(properties, '$.extension_origin')) AS extension_origin"),
            DB::raw('COUNT(*) AS aggregate_count'),
            DB::raw('MIN(created_at) AS first_seen_at'),
            DB::raw('MAX(created_at) AS last_seen_at'),
        ])->groupBy('action', 'description_key', 'loggable_type', 'extension_origin')->get();

        foreach ($rows as $row) {
            $log = new ActivityLog([
                'action' => $row->action,
                'description_key' => $row->description_key,
                'loggable_type' => $row->loggable_type,
                'properties' => $row->extension_origin ? ['extension_origin' => $row->extension_origin] : [],
            ]);
            $source = $this->sourceResolver->resolve($log);

            foreach ($definitions as $definition) {
                if ($definition->expectedAction !== $row->action
                    || $definition->descriptionKey !== $row->description_key
                    || $definition->sourceType !== $source['type']
                    || $definition->sourceIdentifier !== $source['identifier']) {
                    continue;
                }

                $current = $result[$definition->key];
                $count = (int) $row->aggregate_count;
                $first = (string) $row->first_seen_at;
                $last = (string) $row->last_seen_at;
                $result[$definition->key] = [
                    'observed' => true,
                    'count' => $current['count'] + $count,
                    'first_seen_at' => $current['first_seen_at'] === null || $first < $current['first_seen_at'] ? $first : $current['first_seen_at'],
                    'last_seen_at' => $current['last_seen_at'] === null || $last > $current['last_seen_at'] ? $last : $current['last_seen_at'],
                ];
            }
        }

        return $result;
    }
}
