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
        'class' => 'Opis\\Colibri\\Containers\\RouteCollector',
        'description' => 'Collects web routes',
    ),
    'routealiases' => array(
        'class' => 'Opis\\Colibri\\Containers\\RouteAliasCollector',
        'description' => 'Collects aliases for web routes',
    ),
    'views' => array(
        'class' => 'Opis\\Colibri\\Containers\\ViewCollector',
        'description' => 'Collects views',
    ),
    'dispatchers' => array(
        'class' => 'Opis\\Colibri\\Containers\\DispatcherCollector',
        'description' => 'Collects dispatchers',
    ),
    'contracts' => array(
        'class' => 'Opis\\Colibri\\Containers\\ContractCollector',
        'description' => 'Collects contracts',
    ),
    'connections' => array(
        'class' => 'Opis\\Colibri\\Containers\\ConnectionCollector',
        'description' => 'Collects database connections',
    ),
    'eventhandlers' => array(
        'class' => 'Opis\\Colibri\\Containers\\EventHandlerCollector',
        'description' => 'Collects event handlers',
    ),
    'viewengines' => array(
        'class' => 'Opis\\Colibri\\Containers\\ViewEngineCollector',
        'description' => 'Collects view engines',
    ),
    'cachedrivers' => array(
        'class' => 'Opis\\Colibri\\Containers\\CacheCollector',
        'description' => 'Collects cache drivers',
    ),
    'sessionhandlers' => array(
        'class' => 'Opis\\Colibri\\Containers\\SessionCollector',
        'description' => 'Collects session handlers',
    ),
    'configdrivers' => array(
        'class' => 'Opis\\Colibri\\Containers\\ConfigCollector',
        'description' => 'Collects config drivers'
    ),
    'validators' => array(
        'class' => 'Opis\\Colibri\\Containers\\ValidatorCollector',
        'description' => 'Collects validators',
    ),
    'translations' => array(
        'class' => 'Opis\\Colibri\\Containers\\TranslationCollector',
        'description' => 'Collects translations',
    ),
    'variables' => array(
        'class' => 'Opis\\Colibri\\Containers\\VariableCollector',
        'description' => 'Collects variables',
    ),
    'commands' => array(
        'class' => 'Opis\\Colibri\\Containers\\CommandCollector',
        'description' => 'Collects commands',
    ),
    'loggers' => array(
        'class' => 'Opis\\Colibri\\Containers\\LoggerCollector',
        'description' => 'Collects log storages',
    ),
);