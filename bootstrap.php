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
    'ROOT_PATH' => __DIR__,
    'CORE_PATH' => __DIR__ . '/vendor/opis-colibri/core',
    'PUBLIC_PATH' => __DIR__ . '/public',
    'ASSETS_PATH' => __DIR__ . '/public/assets',
    'MODULES_PATH' => __DIR__ . '/modules',
    'STORAGES_PATH' => __DIR__ . '/storage',
    'SYSTEM_PATH' => __DIR__ . '/system',
    'SYSTEM_MODULES_PATH' => __DIR__ . '/system/modules',
    'INSTALL_MODE' => !file_exists(__DIR__ . '/storage/app.php'),
    'CLI_MODE' => php_sapi_name() == 'cli',
    'APP_FILE' => 'app.php',
    'APP_CLASS' => 'Opis\Colibri\App'
));

$app = new Application($appInfo);

$app->bootstrap();

$app->init();

return $app;
