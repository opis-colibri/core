<?php

// Collectors

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
    'configstorages' => array(
        'class' => 'Opis\\Colibri\\Collectors\\ConfigCollector',
        'description' => 'Collects config storages'
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
    'coremethods' => array(
        'class' => 'Opis\\Colibri\\Collectors\\CoreMethodCollector',
        'description' => 'Collects core methods',
    ),
    'loggers' => array(
        'class' => 'Opis\\Colibri\\Collectors\\LoggerCollector',
        'description' => 'Collects log storages',
    ),
);