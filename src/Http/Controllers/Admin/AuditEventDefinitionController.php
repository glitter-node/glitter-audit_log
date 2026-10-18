<?php

namespace Modules\Glitter\AuditLog\Http\Controllers\Admin;

use App\Http\Controllers\Api\Base\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Glitter\AuditLog\Contracts\AuditEventRegistryInterface;
use Modules\Glitter\AuditLog\Http\Resources\AuditEventDefinitionResource;

class AuditEventDefinitionController extends AdminBaseController
{
    public function __construct(private readonly AuditEventRegistryInterface $registry)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $definitions = array_map(
            fn ($definition): array => (new AuditEventDefinitionResource($definition))->toArray($request),
            $this->registry->all(),
        );

        return $this->success(
            'glitter-audit_log::messages.event_registry.fetch_success',
            ['data' => $definitions],
        );
    }
}
