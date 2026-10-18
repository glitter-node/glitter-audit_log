<?php

use Illuminate\Support\Facades\Route;
use Modules\Glitter\AuditLog\Http\Controllers\Admin\AuditCoverageController;
use Modules\Glitter\AuditLog\Http\Controllers\Admin\AuditEventDefinitionController;
use Modules\Glitter\AuditLog\Http\Controllers\Admin\AuditExplorerController;

Route::prefix('admin/audits')
    ->middleware(['auth:sanctum', 'admin', 'throttle:600,1'])
    ->name('admin.audits.')
    ->group(function () {
        Route::get('/', [AuditExplorerController::class, 'index'])
            ->middleware([
                'permission:admin,glitter-audit_log.audits.read',
                'permission:admin,core.activities.read',
            ])
            ->name('index');
    });

Route::prefix('admin/event-definitions')
    ->middleware(['auth:sanctum', 'admin', 'throttle:600,1'])
    ->name('admin.event-definitions.')
    ->group(function () {
        Route::get('/', [AuditEventDefinitionController::class, 'index'])
            ->middleware([
                'permission:admin,glitter-audit_log.audits.read',
                'permission:admin,core.activities.read',
            ])
            ->name('index');
    });

Route::prefix('admin/coverage')
    ->middleware(['auth:sanctum', 'admin', 'throttle:600,1'])
    ->name('admin.coverage.')
    ->group(function () {
        Route::get('/', [AuditCoverageController::class, 'index'])
            ->middleware([
                'permission:admin,glitter-audit_log.audits.read',
                'permission:admin,core.activities.read',
            ])
            ->name('index');
    });
