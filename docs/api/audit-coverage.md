# Audit Coverage API

## Diagnose declared events

```http
GET /api/modules/glitter-audit_log/admin/coverage
```

Required permissions:

- `glitter-audit_log.audits.read`
- `core.activities.read`

Optional query parameters:

| Parameter | Values | Meaning |
|---|---|---|
| `source_type` | `core`, `module`, `plugin`, `template` | Exact declared source type |
| `source_identifier` | extension identifier | Exact declared source identifier |
| `structural_status` | `covered`, `partial`, `uncovered`, `unknown` | Structural result |
| `observed` | boolean | Whether a matching ledger aggregate exists |
| `event_key` | event definition key | Exact event key |

The response is a non-paginated collection because the registry is an in-memory active-extension metadata catalog.

```json
{
  "success": true,
  "message": "Audit event coverage retrieved successfully.",
  "data": {
    "data": [{
      "event": {
        "key": "vendor-module.resource.updated",
        "source": {"type": "module", "identifier": "vendor-module"},
        "category": "content",
        "severity": "info",
        "description": {"key": "vendor-module::activity.resource_update", "text": "Resource updated"},
        "expected_action": "resource.update"
      },
      "structural": {
        "status": "covered",
        "checks": [{"key": "source_activity_listener", "status": "passed", "evidence": "Vendor\\Module\\Listeners\\ActivityLogListener"}]
      },
      "observation": {"observed": false, "count": 0, "first_seen_at": null, "last_seen_at": null},
      "diagnostics": ["no_observation"]
    }]
  }
}
```

Authentication failures return `401`; missing either permission returns `403`. Validation failures return `422` using the standard G7 response shape.

## Semantics and limits

Structural coverage and observational evidence are independent:

- `covered`: the active hook cache exposes an Activity Log listener, the expected action is represented by a subscribed hook, and the description translation exists.
- `partial`: an Activity Log listener exists but one or more other checks are not confirmed.
- `uncovered`: runtime metadata explicitly confirms that the source has no Activity Log listener.
- `unknown`: available runtime metadata is insufficient for a safe conclusion.

`observed=false` means only that no matching record is currently present in the permitted ledger scope. It does **not** mean the event is unimplemented and never changes the structural status.

Observation matches exact `source + expected_action + description_key`. Source is normalized from `properties.extension_origin` and `loggable_type`. Records are grouped in one read-only aggregate query; definitions do not cause per-event queries. Because `description_key` is stored directly in `g7_activity_logs`, it is included in exact matching.

Coverage is transient metadata/runtime analysis. It does not emit logs, guarantee future log emission, register listeners, or persist results in a database.
