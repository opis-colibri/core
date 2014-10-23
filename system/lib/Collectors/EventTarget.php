<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

namespace Opis\Colibri\Collectors;

use InvalidArgumentException;
use Opis\Events\Event as BaseEvent;
use Opis\Events\EventTarget as BaseEventTarget;
use Opis\Container\Container as OpisContainer;
use Opis\Colibri\App;

class EventTarget extends BaseEventTarget
{
    
    protected $container;
    
    protected function container()
    {
        if($this->container === null)
        {   
            $container = new OpisContainer();
            
            foreach(App::systemConfig()->read('collectors') as $type => $collector)
            {
                $container->alias($collector['interface'], $type);
                $container->singleton($collector['interface'], $collector['class']);
            }
            
            $this->container = $container;
        }
        
        return $this->container;
    }
    
    public function dispatch(BaseEvent $event)
    {
        if($event->target() !== $this)
        {
            throw new InvalidArgumentException('Inavlid target');
        }
        elseif(!$event instanceof Event)
        {
            throw new InvalidArgumentException('Invalid event type. Expected \Colibri\System\Collectors\Event');
        }
        
        $this->collection->sort();
        $handlers = $this->router->route($event);
        
        foreach($handlers as $callback)
        {
            $callback($event->getCollector());
        }
        
        return $event->getCollector();
    }
    
    public function collect($type)
    {
        
        if(null === App::systemConfig()->read("collectors.$type"))
        {
            throw new \Exception('Unknown collector type "'.$type.'"');
        }
        
        $collector = $this->container()->make($type);
        
        return $this->dispatch(new Event($this, $type, $collector));
    }
    
}
