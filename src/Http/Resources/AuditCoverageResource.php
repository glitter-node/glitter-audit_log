<?php

namespace Modules\Glitter\AuditLog\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class AuditCoverageResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'event' => (new AuditEventDefinitionResource($this->resource->event))->toArray($request),
            'structural' => [
                'status' => $this->resource->structuralStatus,
                'checks' => $this->resource->structuralChecks,
            ],
            'observation' => $this->resource->observation,
            'diagnostics' => $this->resource->diagnostics,
        ];
    }
}
