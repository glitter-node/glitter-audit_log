<?php

namespace Modules\Glitter\AuditLog\Http\Controllers\Admin;

use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Glitter\AuditLog\Contracts\AuditCoverageServiceInterface;
use Modules\Glitter\AuditLog\Http\Requests\AuditCoverageIndexRequest;
use Modules\Glitter\AuditLog\Http\Resources\AuditCoverageResource;

class AuditCoverageController extends AdminBaseController
{
    public function __construct(private readonly AuditCoverageServiceInterface $coverage)
    {
        parent::__construct();
    }

    public function index(AuditCoverageIndexRequest $request): JsonResponse
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        if (array_key_exists('observed', $filters)) {
            $filters['observed'] = $request->boolean('observed');
        }

        $data = array_map(
            fn ($result): array => (new AuditCoverageResource($result))->toArray($request),
            $this->coverage->all($filters),
        );

        return $this->success('glitter-audit_log::messages.coverage.fetch_success', ['data' => $data]);
    }
}
