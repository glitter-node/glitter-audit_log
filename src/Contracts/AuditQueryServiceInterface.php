<?php

namespace Modules\Glitter\AuditLog\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditQueryServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator;
}
