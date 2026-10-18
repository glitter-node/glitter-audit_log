<?php

namespace Modules\Glitter\AuditLog\Http\Controllers\Admin;

use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Glitter\AuditLog\Contracts\AuditQueryServiceInterface;
use Modules\Glitter\AuditLog\Http\Requests\AuditIndexRequest;
use Modules\Glitter\AuditLog\Http\Resources\AuditLogCollection;

class AuditExplorerController extends AdminBaseController
{
    public function __construct(
        private readonly AuditQueryServiceInterface $queryService,
    ) {
        parent::__construct();
    }

    public function index(AuditIndexRequest $request): JsonResponse
    {
        try {
            $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
            $logs = $this->queryService->paginate($filters);

            return $this->success(
                'glitter-audit_log::messages.audit.fetch_success',
                (new AuditLogCollection($logs))->toArray($request),
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->error('glitter-audit_log::messages.audit.fetch_failed', 500);
        }
    }
}
