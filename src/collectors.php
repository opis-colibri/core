<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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
        'class' => 'Opis\\Colibri\\ItemCollectors\\RouteCollector',
        'description' => 'Collects web routes',
    ),
    'routealiases' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\RouteAliasCollector',
        'description' => 'Collects aliases for web routes',
    ),
    'responseinterceptors' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\ResponseInterceptorCollector',
        'description' => 'Collects response interceptors',
    ),
    'views' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\ViewCollector',
        'description' => 'Collects views',
    ),
    'contracts' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\ContractCollector',
        'description' => 'Collects contracts',
    ),
    'connections' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\ConnectionCollector',
        'description' => 'Collects database connections',
    ),
    'eventhandlers' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\EventHandlerCollector',
        'description' => 'Collects event handlers',
    ),
    'viewengines' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\ViewEngineCollector',
        'description' => 'Collects view engines',
    ),
    'cachedrivers' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\CacheCollector',
        'description' => 'Collects cache drivers',
    ),
    'sessionhandlers' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\SessionCollector',
        'description' => 'Collects session handlers',
    ),
    'configdrivers' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\ConfigCollector',
        'description' => 'Collects config drivers'
    ),
    'validators' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\ValidatorCollector',
        'description' => 'Collects validators',
    ),
    'translations' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\TranslationCollector',
        'description' => 'Collects translations',
    ),
    'variables' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\VariableCollector',
        'description' => 'Collects variables',
    ),
    'commands' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\CommandCollector',
        'description' => 'Collects commands',
    ),
    'loggers' => array(
        'class' => 'Opis\\Colibri\\ItemCollectors\\LoggerCollector',
        'description' => 'Collects log storages',
    ),
);