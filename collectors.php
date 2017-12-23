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

return [
    'routes' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\RouteCollector',
        'description' => 'Collects web routes',
        'invertedPriority' => false,
    ],
    'routealiases' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\RouteAliasCollector',
        'description' => 'Collects aliases for web routes',
        'invertedPriority' => false,
    ],
    'responseinterceptors' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\ResponseInterceptorCollector',
        'description' => 'Collects response interceptors',
        'invertedPriority' => true,
    ],
    'views' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\ViewCollector',
        'description' => 'Collects views',
        'invertedPriority' => false,
    ],
    'contracts' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\ContractCollector',
        'description' => 'Collects contracts',
        'invertedPriority' => true,
    ],
    'connections' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\ConnectionCollector',
        'description' => 'Collects database connections',
        'invertedPriority' => true,
    ],
    'eventhandlers' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\EventHandlerCollector',
        'description' => 'Collects event handlers',
        'invertedPriority' => false,
    ],
    'viewengines' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\ViewEngineCollector',
        'description' => 'Collects view engines',
        'invertedPriority' => true,
    ],
    'cachedrivers' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\CacheCollector',
        'description' => 'Collects cache drivers',
        'invertedPriority' => true,
    ],
    'sessionhandlers' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\SessionCollector',
        'description' => 'Collects session handlers',
        'invertedPriority' => true,
    ],
    'configdrivers' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\ConfigCollector',
        'description' => 'Collects config drivers',
        'invertedPriority' => true,
    ],
    'validators' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\ValidatorCollector',
        'description' => 'Collects validators',
        'invertedPriority' => true,
    ],
    'translations' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\TranslationCollector',
        'description' => 'Collects translations',
        'invertedPriority' => true,
    ],
    'translationfilters' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\TranslationFilterCollector',
        'description' => 'Collect translation filters',
        'invertedPriority' => true,
    ],
    'variables' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\VariableCollector',
        'description' => 'Collects variables',
        'invertedPriority' => true,
    ],
    'commands' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\CommandCollector',
        'description' => 'Collects commands',
        'invertedPriority' => true,
    ],
    'loggers' => [
        'class' => 'Opis\\Colibri\\ItemCollectors\\LoggerCollector',
        'description' => 'Collects log storages',
        'invertedPriority' => true,
    ],
];