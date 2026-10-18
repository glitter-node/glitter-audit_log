# Audit Event Definitions API

## List event definitions

```http
GET /api/modules/glitter-audit_log/admin/event-definitions
```

Required permissions:

- `glitter-audit_log.audits.read`
- `core.activities.read`

The endpoint returns the small in-memory catalog as a non-paginated collection. It accepts no query parameters.

```json
{
  "success": true,
  "message": "Audit event definitions retrieved successfully.",
  "data": {
    "data": [
      {
        "key": "vendor-module.resource.created",
        "source": {"type": "module", "identifier": "vendor-module"},
        "category": "content",
        "severity": "info",
        "description": {
          "key": "vendor-module::activity.resource_created",
          "text": "Resource created"
        },
        "expected_action": "resource.create"
      }
    ]
  }
}
```

Authentication failures return `401`; missing either permission returns `403` using the standard G7 error response.

## Declaration contract

Active modules and plugins may implement `ProvidesAuditEventsInterface`, or provide the equivalent optional `getAuditEvents(): array` convention method. Active templates may declare the same list under `audit_events` in `template.json`.

Every declaration requires `key`, `source_type`, `source_identifier`, `category`, `severity`, `description_key`, and `expected_action`.

`source_type` is one of `core`, `module`, `plugin`, or `template`. Severity is one of `info`, `notice`, `warning`, or `critical`. Category is a non-empty, extensible string; an unfamiliar category is preserved rather than rejected.

The declared source must match the active extension that owns the declaration. Duplicate keys and invalid definitions fail explicitly and are never silently overwritten.

## Semantics

An Event Definition is metadata, not an `activity_logs` row. Registering a definition does not write a log, install a listener, or guarantee that an activity event is emitted. Coverage compares definitions with runtime metadata and existing records, while observation remains evidence rather than proof of complete structural integration.
