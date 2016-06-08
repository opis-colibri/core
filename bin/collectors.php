<?php

// Collectors

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