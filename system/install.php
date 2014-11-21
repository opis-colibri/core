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

use Opis\Colibri\App;
use Opis\Colibri\ClassLoader;
use Opis\Colibri\Module;

$composerContent = json_decode(file_get_contents(COLIBRI_ROOT . '/composer.json'), true);

$enabled_modules = array();

foreach($composerContent['extra']['installer-modules'] as $module)
{
    $enabled_modules[$module] = true;
}

$modules = array_filter(Module::findAll(), function(&$value) use($enabled_modules){
    return isset($enabled_modules[$value['name']]);
});

App::systemConfig()->write('modules', array(
    'enabled' => $enabled_modules,
    'list' => $modules,
));

App::systemConfig()->write('collectors', array(
    'Routes' => array(
        'interface' => 'Opis\\Colibri\\RouteCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\RouteCollector',
    ),
    'RouteAliases' => array(
        'interface' => 'Opis\\Colibri\\RouteAliasCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\RouteAliasCollector',
    ),
    'Views' => array(
        'interface' => 'Opis\\Colibri\\ViewCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\ViewCollector',
    ),
    'Dispatchers' => array(
        'interface' => 'Opis\\Colibri\\DispatcherCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\DispatcherCollector',
    ),
    'Contracts' => array(
        'interface' => 'Opis\\Colibri\\ContractCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\ContractCollector',
    ),
    'Connections' => array(
        'interface' => 'Opis\\Colibri\\ConnectionCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\ConnectionCollector',
    ),
    'EventHandlers' => array(
        'interface' => 'Opis\\Colibri\\EventHandlerCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\EventHandlerCollector',
    ),
    'ViewEngines' => array(
        'interface' => 'Opis\\Colibri\\ViewEngineCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\ViewEngineCollector',
    ),
    'CacheStorages' => array(
        'interface' => 'Opis\\Colibri\\CacheCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\CacheCollector',
    ),
    'SessionStorages' => array(
        'interface' => 'Opis\\Colibri\\SessionCollectorInterface',
        'class' => 'Opis\\Colibri\\Collectors\\SessionCollector',
    ),
    'ConfigStorages' => array(
        'interface' => 'Opis\\Colibri\\ConfigCollectorInterface',
        'class' => 'Opis\\Colibri\\Colectors\\ConfigCollector',
    ),
));

