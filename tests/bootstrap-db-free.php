<?php

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;

$root = dirname(__DIR__, 4);
$loader = require $root.'/vendor/autoload.php';

$moduleRoot = dirname(__DIR__);
$loader->addPsr4('Modules\\Glitter\\AuditLog\\', $moduleRoot.'/src', true);
$loader->addPsr4('Modules\\Glitter\\AuditLog\\Tests\\', $moduleRoot.'/tests', true);
require_once $moduleRoot.'/module.php';

$app = new Application($root);
$app->instance('config', new Repository([
    'app' => [
        'locale' => 'ko',
        'fallback_locale' => 'en',
        'default_user_timezone' => 'Asia/Seoul',
    ],
]));

$translator = new Translator(
    new ArrayLoader,
    'ko',
);
$translator->addLines([
    'activity_log.type.admin' => '관리자',
    'activity_log.action.update' => '수정',
    'activity_log.description.user_update' => '사용자 수정',
    'common.system' => '시스템',
], 'ko');
$app->instance('translator', $translator);
$app->setLocale('ko');

Facade::setFacadeApplication($app);
