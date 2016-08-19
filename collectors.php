<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

return array(
    'routes' => array(
        'class' => 'Opis\\Colibri\\Collectors\\RouteCollector',
        'description' => 'Collects web routes',
    ),
    'routealiases' => array(
        'class' => 'Opis\\Colibri\\Collectors\\RouteAliasCollector',
        'description' => 'Collects aliases for web routes',
    ),
    'views' => array(
        'class' => 'Opis\\Colibri\\Collectors\\ViewCollector',
        'description' => 'Collects views',
    ),
    'dispatchers' => array(
        'class' => 'Opis\\Colibri\\Collectors\\DispatcherCollector',
        'description' => 'Collects dispatchers',
    ),
    'contracts' => array(
        'class' => 'Opis\\Colibri\\Collectors\\ContractCollector',
        'description' => 'Collects contracts',
    ),
    'connections' => array(
        'class' => 'Opis\\Colibri\\Collectors\\ConnectionCollector',
        'description' => 'Collects database connections',
    ),
    'eventhandlers' => array(
        'class' => 'Opis\\Colibri\\Collectors\\EventHandlerCollector',
        'description' => 'Collects event handlers',
    ),
    'viewengines' => array(
        'class' => 'Opis\\Colibri\\Collectors\\ViewEngineCollector',
        'description' => 'Collects view engines',
    ),
    'cachestorages' => array(
        'class' => 'Opis\\Colibri\\Collectors\\CacheCollector',
        'description' => 'Collects cache storages',
    ),
    'sessionstorages' => array(
        'class' => 'Opis\\Colibri\\Collectors\\SessionCollector',
        'description' => 'Collects session storages',
    ),
    'configdrivers' => array(
        'class' => 'Opis\\Colibri\\Collectors\\ConfigCollector',
        'description' => 'Collects config drives'
    ),
    'validators' => array(
        'class' => 'Opis\\Colibri\\Collectors\\ValidatorCollector',
        'description' => 'Collects validators',
    ),
    'translations' => array(
        'class' => 'Opis\\Colibri\\Collectors\\TranslationCollector',
        'description' => 'Collects translations',
    ),
    'variables' => array(
        'class' => 'Opis\\Colibri\\Collectors\\VariableCollector',
        'description' => 'Collects variables',
    ),
    'commands' => array(
        'class' => 'Opis\\Colibri\\Collectors\\CommandCollector',
        'description' => 'Collects commands',
    ),
    'loggers' => array(
        'class' => 'Opis\\Colibri\\Collectors\\LoggerCollector',
        'description' => 'Collects log storages',
    ),
);