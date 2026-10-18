<?php

namespace Modules\Glitter\AuditLog;

use App\Extension\AbstractModule;

class Module extends AbstractModule
{
    /**
     * 이 모듈이 선언하는 감사 이벤트 메타데이터입니다.
     *
     * 현재 모듈은 읽기 전용 기능만 제공하므로 실제 발생하지 않는 이벤트를 선언하지 않습니다.
     *
     * @return array<int, array<string, string>>
     */
    public function getAuditEvents(): array
    {
        return [];
    }

    public function getRoles(): array
    {
        return [];
    }

    public function getPermissions(): array
    {
        return [
            'name' => ['ko' => '감사 로그', 'en' => 'Audit Log'],
            'description' => [
                'ko' => '코어 활동 로그 기반 감사 조회 권한',
                'en' => 'Audit query permissions over core activity logs',
            ],
            'categories' => [
                [
                    'identifier' => 'audits',
                    'resource_route_key' => null,
                    'owner_key' => 'user_id',
                    'name' => ['ko' => '감사 조회', 'en' => 'Audit Queries'],
                    'description' => [
                        'ko' => '감사 로그 조회 권한',
                        'en' => 'Audit log query permission',
                    ],
                    'permissions' => [
                        [
                            'action' => 'read',
                            'name' => ['ko' => '감사 로그 조회', 'en' => 'View Audit Logs'],
                            'description' => [
                                'ko' => '허용된 범위의 감사 로그를 조회합니다.',
                                'en' => 'View audit logs within the permitted scope.',
                            ],
                            'type' => 'admin',
                            'roles' => ['admin'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getAdminMenus(): array
    {
        return [
            [
                'name' => ['ko' => '감사 로그', 'en' => 'Audit Log'],
                'slug' => 'glitter-audit-log',
                'icon' => 'fas fa-clipboard-check',
                'order' => 90,
                'permission' => 'glitter-audit_log.audits.read',
                'children' => [
                    [
                        'name' => ['ko' => '감사 로그 탐색', 'en' => 'Audit Explorer'],
                        'slug' => 'glitter-audit-log-explorer',
                        'url' => '/admin/modules/glitter-audit_log/audits',
                        'icon' => 'fas fa-list',
                        'order' => 1,
                        'permission' => 'glitter-audit_log.audits.read',
                    ],
                    [
                        'name' => ['ko' => 'Coverage 진단', 'en' => 'Coverage Diagnostics'],
                        'slug' => 'glitter-audit-log-coverage',
                        'url' => '/admin/modules/glitter-audit_log/coverage',
                        'icon' => 'fas fa-link',
                        'order' => 2,
                        'permission' => 'glitter-audit_log.audits.read',
                    ],
                ],
            ],
        ];
    }
}
