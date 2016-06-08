<?php
/**
 * Created by PhpStorm.
 * User: mari
 * Date: 08.06.2016
 * Time: 23:22
 */

namespace Opis\Colibri;

use InvalidArgumentException;
use Opis\Events\Event as BaseEvent;
use Opis\Events\EventTarget;
use Opis\Events\RouteCollection;

class CollectorTarget extends EventTarget
{
    /** @var  Application */
    protected $app;

    /**
     * CollectorTarget constructor.
     *
     * @param Application $app
     * @param RouteCollection|null $collection
     */
    public function __construct(Application $app, RouteCollection $collection = null)
    {
        $this->app = $app;
        parent::__construct($collection);
    }

    /**
     * @param BaseEvent $event
     *
     * @return Collectors\AbstractCollector
     */
    public function dispatch(BaseEvent $event)
    {
        if (!$event instanceof CollectorEntry) {
            throw new InvalidArgumentException('Invalid event type. Expected ' . CollectorEntry::class);
        }

        $this->collection->sort();
        $handlers = $this->router->route($event);
        $collector = $event->getCollector();

        foreach ($handlers as $callback) {
            $callback($collector, $this->app);
        }

        return $collector;
    }
}