# 감사 로그 API

## GET /api/modules/glitter-audit_log/admin/audits

G7 코어 `activity_logs` 원장을 변경하지 않고 감사 관점으로 조회합니다.

- 라우트명: `api.modules.glitter-audit_log.admin.audits.index`
- 인증: `auth:sanctum`, 관리자
- 필수 권한: `glitter-audit_log.audits.read`와 `core.activities.read` 모두 필요
- 원장 scope: `core.activities.read`의 self/role/global scope를 그대로 적용

### Query parameters

| 이름 | 타입 | 설명 |
|---|---|---|
| `log_type` | string 또는 array | `admin`, `user`, `system` |
| `action` | string | 정확한 action 값 |
| `actor` | string | actor UUID 또는 이메일 |
| `resource_type` | string | 정확한 `loggable_type` FQCN |
| `resource_id` | integer | `loggable_id` |
| `source_type` | string | `core`, `module`, `plugin`, `template`, `unknown` |
| `source_identifier` | string | 확장 identifier 또는 `core` |
| `date_from` | date | 조회 시작 일시 |
| `date_to` | date | 조회 종료 일시 |
| `search` | string | action, description key, IP, resource type, actor 이름/이메일 검색 |
| `per_page` | integer | 1~100, 기본 20 |
| `page` | integer | 페이지 번호 |

`source_*`는 원장 컬럼이 아니라 `properties.extension_origin`과 `loggable_type`에서
판별합니다. 전체 로그를 애플리케이션 메모리로 읽지는 않지만, JSON expression에는 전용
인덱스가 없어 큰 원장에서는 일반 인덱스 필터보다 느릴 수 있습니다. 가능한 경우 날짜나
`source_identifier`를 함께 사용해야 합니다.

### 성공 응답

```json
{
  "success": true,
  "message": "감사 로그를 조회했습니다.",
  "data": {
    "data": [
      {
        "id": 1,
        "type": {"value": "admin", "label": "관리자"},
        "action": {"value": "page.update", "label": "수정"},
        "actor": {"name": "관리자", "user": {"uuid": "...", "name": "관리자", "email": "admin@example.com"}},
        "resource": {"type": "Modules\\Sirsoft\\Page\\Models\\Page", "type_display": "Page", "id": 10},
        "source": {"type": "module", "identifier": "sirsoft-page"},
        "ip": "127.0.0.1",
        "created_at": "2026-08-29 12:00:00",
        "description": {"text": "페이지 수정", "key": "sirsoft-page::activity_log.description.page_update"},
        "changes": [],
        "bulk_changes": null,
        "properties": {}
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "from": 1,
      "to": 1,
      "has_more_pages": false,
      "last_page": 1,
      "total": 1
    }
  }
}
```

User-Agent는 코어 `ActivityLogResource`의 노출 정책에 따라 응답하지 않습니다.

### 오류 응답

| 상태 | 조건 |
|---|---|
| 401 | 인증되지 않음 |
| 403 | 관리자 또는 두 read 권한 중 하나가 없음 |
| 422 | query parameter 검증 실패 |
| 500 | 조회 실패 |
