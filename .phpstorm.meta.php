<?php
namespace PHPSTORM_META {
    override(\Opis\Colibri\Functions\make(0), type(0));

    override(\Opis\Colibri\Collector\Manager::collect(0), map([
        'routes' => \Opis\Colibri\Routing\HttpRouteCollection::class,
        'router-globals' => \Opis\Colibri\Serializable\RouterGlobals::class,
        'middleware' => \Opis\Colibri\Serializable\ClassList::class,
        'views' => \Opis\View\RouteCollection::class,
        'contracts' => \Opis\Colibri\Container::class,
        'connections' => \Opis\Colibri\Serializable\ConnectionList::class,
        'event-handlers' => \Opis\Events\RouteCollection::class,
        'view-engines' => \Opis\Colibri\Serializable\ViewEngineResolver::class,
        'cache-drivers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'session-handlers' => \Opis\Colibri\Serializable\CallbackList::class,
        'config-drivers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'translations' => \Opis\Colibri\Serializable\Translations::class,
        'translation-filters' => \Opis\Colibri\Serializable\ClassList::class,
        'commands' => \Opis\Colibri\Serializable\CallbackList::class,
        'loggers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'loggers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'asset-handlers' => \Opis\Colibri\Serializable\CallbackList::class,
        'validators' => \Opis\Colibri\Serializable\ClassList::class,
        'template-stream-handlers' => \Opis\Colibri\Serializable\AdvancedClassList::class,
    ]));

    override(\Opis\Colibri\Functions\collect(0), map([
        'routes' => \Opis\Colibri\Routing\HttpRouteCollection::class,
        'router-globals' => \Opis\Colibri\Serializable\RouterGlobals::class,
        'middleware' => \Opis\Colibri\Serializable\ClassList::class,
        'views' => \Opis\View\RouteCollection::class,
        'contracts' => \Opis\Colibri\Container::class,
        'connections' => \Opis\Colibri\Serializable\ConnectionList::class,
        'event-handlers' => \Opis\Events\RouteCollection::class,
        'view-engines' => \Opis\Colibri\Serializable\ViewEngineResolver::class,
        'cache-drivers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'session-handlers' => \Opis\Colibri\Serializable\SessionList::class,
        'config-drivers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'translations' => \Opis\Colibri\Serializable\Translations::class,
        'translation-filters' => \Opis\Colibri\Serializable\ClassList::class,
        'commands' => \Opis\Colibri\Serializable\CallbackList::class,
        'loggers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'loggers' => \Opis\Colibri\Serializable\StorageCollection::class,
        'asset-handlers' => \Opis\Colibri\Serializable\CallbackList::class,
        'validators' => \Opis\Colibri\Serializable\ClassList::class,
        'template-stream-handlers' => \Opis\Colibri\Serializable\AdvancedClassList::class,
    ]));
}

namespace Opis\Validation\Types {
    class Field {
        /**
         * @param bool|true $remove
         * @return self
         */
        public function csrf(bool $remove = true): self {}
    }
}