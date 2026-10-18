<?php

namespace Modules\Glitter\AuditLog\Http\Resources;

use App\Http\Resources\ActivityLogResource;

/**
 * 코어 ActivityLog 표현을 재사용하되 이 모듈 응답에 포함하지 않는 row ability 조회는 생략합니다.
 */
class CoreAuditLogProjectionResource extends ActivityLogResource
{
    protected function abilityMap(): array
    {
        return [];
    }

    protected function ownerField(): ?string
    {
        return null;
    }
}
