<?php
/* ===========================================================================
 * Copyright 2013-2018 The Opis Project
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

use Opis\Colibri\Application;

error_reporting(-1);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('opcache.enable', 0);

require __DIR__ . '/../../vendor/autoload.php';

spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');

    $map = [
        __DIR__ . '/modules/Foo/src' => 'Test\\Foo\\',
        __DIR__ . '/modules/Bar/src' => 'Test\\Bar\\',
    ];

    foreach ($map as $dir => $namespace) {
        if (strpos($class, $namespace) === 0) {
            $class = substr($class, strlen($namespace));
            $path = '';
            if (($pos = strripos($class, '\\')) !== false) {
                $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
                $class = substr($class, $pos + 1);
            }
            $path .= str_replace('_', '/', $class) . '.php';
            $dir .= '/' . $path;
            if (file_exists($dir)) {
                /** @noinspection PhpIncludeInspection */
                include $dir;
                return true;
            }
            return false;
        }
    }

    return false;
});

$app = new Application(__DIR__);

return $app->bootstrap();
