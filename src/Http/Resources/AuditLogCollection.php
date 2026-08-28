<?php

namespace Modules\Glitter\AuditLog\Http\Resources;

use App\Http\Resources\BaseApiCollection;
use Illuminate\Http\Request;

class AuditLogCollection extends BaseApiCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(
                fn ($log) => (new AuditLogResource($log))->toArray($request)
            ),
            ...$this->paginationMeta(),
        ];
    }
}
