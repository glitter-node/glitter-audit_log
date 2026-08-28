<?php

namespace Modules\Glitter\AuditLog\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class AuditEventDefinitionResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource->key,
            'source' => [
                'type' => $this->resource->sourceType,
                'identifier' => $this->resource->sourceIdentifier,
            ],
            'category' => $this->resource->category,
            'severity' => $this->resource->severity,
            'description' => [
                'key' => $this->resource->descriptionKey,
                'text' => __($this->resource->descriptionKey),
            ],
            'expected_action' => $this->resource->expectedAction,
        ];
    }
}
