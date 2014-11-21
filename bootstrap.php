<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

define('COLIBRI_ROOT', __DIR__);
define('COLIBRI_FRAMEWORK_PATH', COLIBRI_ROOT . '/vendor/opis-colibri/framework');
define('COLIBRI_PUBLIC_PATH', COLIBRI_ROOT . '/public');
define('COLIBRI_PUBLIC_ASSETS_PATH', COLIBRI_PUBLIC_PATH . '/assets');
define('COLIBRI_MODULES_PATH', COLIBRI_ROOT . '/modules');
define('COLIBRI_STORAGES_PATH', COLIBRI_ROOT . '/storage');
define('COLIBRI_SYSTEM_PATH', COLIBRI_ROOT . '/system');
define('COLIBRI_SYSTEM_MODULES_PATH', COLIBRI_SYSTEM_PATH . '/modules');
define('COLIBRI_CLI_MODE', false);

require_once 'vendor/autoload.php';


define('COLIBRI_INSTALL_MODE', !file_exists(COLIBRI_STORAGES_PATH . '/site.php'));

if(COLIBRI_INSTALL_MODE)
{
    require_once COLIBRI_SYSTEM_PATH . '/install.php';
}
elseif(file_exists(COLIBRI_ROOT . '/site.php'))
{
    require_once COLIBRI_ROOT . '/site.php';
}
else
{
    require_once COLIBRI_STORAGES_PATH . '/site.php';
}

\Opis\Colibri\App::init();

\Opis\Colibri\App::run();
