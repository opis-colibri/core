<?php
/* ============================================================================
 * CCopyright 2013-2018 The Opis Project
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

use Opis\Colibri\{
    IBootstrap,
    ISettingsContainer
};
use Psr\Log\NullLogger as Logger;
use Opis\Cache\Drivers\Memory as CacheDriver;
use Opis\DataStore\Drivers\Memory as ConfigDriver;
use Opis\Intl\Translator\Drivers\Memory as TranslatorDriver;

return new class implements IBootstrap
{
    public function bootstrap(ISettingsContainer $app)
    {
        date_default_timezone_set('Europe/Bucharest');

        $modules = ['test/foo', 'test/bar'];

        $config = new ConfigDriver([
            'modules' => [
                'installed' => $modules,
                'enabled' => $modules,
            ],
        ]);

        $translation = new TranslatorDriver([], []);

        $app->setCacheDriver(new CacheDriver())
            ->setConfigDriver($config)
            ->setTranslatorDriver($translation)
            ->setDefaultLogger(new Logger())
            ->setSessionHandler(new \SessionHandler());
    }
};
