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

namespace Opis\Colibri;

/**
 * Description of CollectorManager
 *
 * @author mari
 */
class CollectorManager
{
    /** @var    Application */
    protected $app;

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Update collectors list
     */
    public function updateCollectors()
    {
        $collectors = $this->app->config()->read('collectors');
        $collectors += $this->getCollectors();
        $this->app->config()->write('collectors', $collectors);
    }

    /**
     * Register a new collector
     *
     * @param   string $name
     * @param   string $interface
     * @param   string $class
     */
    public function registerCollector($name, $interface, $class)
    {
        $name = strtolower($name);
        $collectors = $this->app->config()->read('collectors');
        $collectors += $this->getCollectors();

        $collectors[$name] = array(
            'interface' => $interface,
            'class' => $class,
        );

        $this->app->config()->write('collectors', $collectors);

        $container = $this->app->getCollector()->container();
        $container->alias($interface, $name);
        $container->singleton($interface, $class);
    }

    /**
     * Unregister a collector
     *
     * @param   string $name
     */
    public function unregisterCollector($name)
    {
        $name = strtolower($name);
        $collectors = $this->app->config()->read('collectors');
        $collectors += $this->getCollectors();
        unset($collectors[$name]);
        $this->app->config()->write('collectors', $collectors);
    }

    /**
     * Get a list of default collectors
     *
     * @return  array
     */
    public function getCollectors()
    {
        return array(
            'routes' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\RouteCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\RouteCollector',
            ),
            'routealiases' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\RouteAliasCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\RouteAliasCollector',
            ),
            'views' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\ViewCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\ViewCollector',
            ),
            'dispatchers' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\DispatcherCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\DispatcherCollector',
            ),
            'contracts' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\ContractCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\ContractCollector',
            ),
            'connections' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\ConnectionCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\ConnectionCollector',
            ),
            'eventhandlers' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\EventHandlerCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\EventHandlerCollector',
            ),
            'viewengines' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\ViewEngineCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\ViewEngineCollector',
            ),
            'cachestorages' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\CacheCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\CacheCollector',
            ),
            'sessionstorages' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\SessionCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\SessionCollector',
            ),
            'configstorages' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\ConfigCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\ConfigCollector',
            ),
            'validators' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\ValidatorCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\ValidatorCollector',
            ),
            'translations' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\TranslationCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\TranslationCollector',
            ),
            'variables' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\VariableCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\VariableCollector',
            ),
            'commands' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\CommandCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\CommandCollector',
            ),
            'coremethods' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\CoreMethodCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\CoreMethodCollector',
            ),
            'loggers' => array(
                'interface' => 'Opis\\Colibri\\Collectors\\LoggerCollectorInterface',
                'class' => 'Opis\\Colibri\\Collectors\\Implementation\\LoggerCollector',
            ),
        );
    }
}
