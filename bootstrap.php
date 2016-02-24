<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

require_once 'vendor/autoload.php';

use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;

$appInfo = new AppInfo(array(
    AppInfo::ROOT_PATH => __DIR__,
    AppInfo::CORE_PATH => __DIR__ . '/vendor/opis-colibri/core',
    AppInfo::PUBLIC_PATH => __DIR__ . '/public',
    AppInfo::ASSETS_PATH => __DIR__ . '/public/assets',
    AppInfo::MODULES_PATH => __DIR__ . '/modules',
    AppInfo::STORAGES_PATH => __DIR__ . '/storage',
    AppInfo::SYSTEM_PATH => __DIR__ . '/system',
    AppInfo::SYSTEM_MODULES_PATH => __DIR__ . '/system/modules',
    AppInfo::INSTALL_MODE => !file_exists(__DIR__ . '/storage/app.php'),
    AppInfo::CLI_MODE => php_sapi_name() == 'cli',
    AppInfo::MAIN_APP_FILE => __DIR__ . '/storage/app.php',
    AppInfo::USER_APP_FILE => __DIR__ . '/app.php',
    AppInfo::APP_CLASS => 'Opis\Colibri\App'
));

$app = new Application($appInfo);

$app->bootstrap();

$app->init();

return $app;
