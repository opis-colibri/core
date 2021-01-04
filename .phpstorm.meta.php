<?php
namespace PHPSTORM_META {

    override(\Opis\Colibri\make(0), type(0));


    override(\Opis\Colibri\ItemCollector::collect(0), map([
        '\Opis\Colibri\Collectors\AssetsHandlerCollector' => \Opis\Colibri\Serializable\Collection::class,
        '\Opis\Colibri\Collectors\CacheCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\CommandCollector' => \Opis\Colibri\Serializable\Collection::class,
        '\Opis\Colibri\Collectors\ConfigCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\ConnectionCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\ContractCollector' => \Opis\Colibri\IoC\Container::class,
        '\Opis\Colibri\Collectors\EventHandlerCollector' => \Opis\Colibri\Events\EventDispatcher::class,
        '\Opis\Colibri\Collectors\JsonSchemaResolversCollector' => \Opis\Colibri\Serializable\JsonSchemaResolvers::class,
        '\Opis\Colibri\Collectors\LoggerCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\RouteCollector' => \Opis\Colibri\Routing\RouteCollection::class,
        '\Opis\Colibri\Collectors\RouterGlobalsCollector' => \Opis\Colibri\Serializable\RouterGlobals::class,
        '\Opis\Colibri\Collectors\SessionCollector' => \Opis\Colibri\Serializable\SessionCollection::class,
        '\Opis\Colibri\Collectors\TemplateStreamHandlerCollector' => \Opis\Colibri\Serializable\AdvancedClassList::class,
        '\Opis\Colibri\Collectors\TranslationCollector' => \Opis\Colibri\Serializable\Translations::class,
        '\Opis\Colibri\Collectors\TranslationFilterCollector' => \Opis\Colibri\Serializable\Collection::class,
        '\Opis\Colibri\Collectors\ViewCollector' => \Opis\Colibri\Render\Renderer::class,
        '\Opis\Colibri\Collectors\RenderEngineCollector' => \Opis\Colibri\Serializable\Collection::class,
    ]));

    override(\Opis\Colibri\collect(0), map([
        '\Opis\Colibri\Collectors\AssetsHandlerCollector' => \Opis\Colibri\Serializable\Collection::class,
        '\Opis\Colibri\Collectors\CacheCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\CommandCollector' => \Opis\Colibri\Serializable\Collection::class,
        '\Opis\Colibri\Collectors\ConfigCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\ConnectionCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\ContractCollector' => \Opis\Colibri\IoC\Container::class,
        '\Opis\Colibri\Collectors\EventHandlerCollector' => \Opis\Colibri\Events\EventDispatcher::class,
        '\Opis\Colibri\Collectors\JsonSchemaResolversCollector' => \Opis\Colibri\Serializable\JsonSchemaResolvers::class,
        '\Opis\Colibri\Collectors\LoggerCollector' => \Opis\Colibri\Serializable\FactoryCollection::class,
        '\Opis\Colibri\Collectors\RouteCollector' => \Opis\Colibri\Routing\RouteCollection::class,
        '\Opis\Colibri\Collectors\RouterGlobalsCollector' => \Opis\Colibri\Serializable\RouterGlobals::class,
        '\Opis\Colibri\Collectors\SessionCollector' => \Opis\Colibri\Serializable\SessionCollection::class,
        '\Opis\Colibri\Collectors\TemplateStreamHandlerCollector' => \Opis\Colibri\Serializable\AdvancedClassList::class,
        '\Opis\Colibri\Collectors\TranslationCollector' => \Opis\Colibri\Serializable\Translations::class,
        '\Opis\Colibri\Collectors\TranslationFilterCollector' => \Opis\Colibri\Serializable\Collection::class,
        '\Opis\Colibri\Collectors\ViewCollector' => \Opis\Colibri\Render\Renderer::class,
        '\Opis\Colibri\Collectors\RenderEngineCollector' => \Opis\Colibri\Serializable\Collection::class,
    ]));
}
