<?php

namespace Modules\Glitter\AuditLog\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;
use Modules\Glitter\AuditLog\Support\AuditSourceResolver;

class AuditLogResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        // 코어 Resource를 보안·번역·changes 표현의 단일 기준으로 재사용한다.
        $core = (new CoreAuditLogProjectionResource($this->resource))->toArray($request);
        $source = $this->resource->getAttribute('audit_source')
            ?? app(AuditSourceResolver::class)->resolve($this->resource);

        return [
            'id' => $core['id'],
            'type' => [
                'value' => $core['log_type'],
                'label' => $core['log_type_label'],
            ],
            'action' => [
                'value' => $core['action'],
                'label' => $core['action_label'],
            ],
            'actor' => [
                'name' => $core['actor_name'],
                'user' => $core['user'],
            ],
            'resource' => [
                'type' => $core['loggable_type'],
                'type_display' => $core['loggable_type_display'],
                'id' => $core['loggable_id'],
            ],
            'source' => $source,
            'ip' => $core['ip_address'],
            'created_at' => $core['created_at'],
            'description' => [
                'text' => $core['localized_description'],
                'key' => $core['description_key'],
            ],
            'changes' => $core['changes'],
            'bulk_changes' => $core['bulk_changes'],
            'properties' => $core['properties'],
        ];
    }
}
