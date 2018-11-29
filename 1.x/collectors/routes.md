---
layout: project
version: 1.x
title: Routes
description: Learn how to collect routes
---
# Routes

* [Creating routes](#creating-routes)
* [HTTP verbs](#b)
* [Regex constraints](#c)
* [Route variables](#d)

**Name:** `routes`{:.text-secondary}  
**Class:** `Opis\Colibri\ItemsCollector\RouteCollector`{:.text-secondary}

## Creating routes

Routes are created by simply defining an URI path and an associated callback that will be invoked
when the route is executed.

```php
namespace Vendor\Module;

use Opis\Colibri\{
    Collector as AbstractCollector,
    ItemCollectors\RouteCollector
};

class Collector extends AbstractCollector
{
    public function routes(RouteCollector $route)
    {
        $route->get('/', function(){
            return 'Hello world';
        });
        
        $route->post('/', function(){
            return 'Hello world';
        });      
        
        $route->put('/', function(){
            return 'Hello world';
        }); 
        
        $route->patch('/', function(){
            return 'Hello world';
        });
        
        $route->delete('/', function(){
            return 'Hello world';
        });
    }
}
```

Another way of creating a route is through direct invocation of the collector object.

```php
// Implicit GET
$route('/', function(){
    return 'Hello world';
});

// Explicit GET
$route('/', function(){
    return 'Hello world';
}, 'GET');

$route('/', function(){
    return 'Hello world';
}, 'POST');

$route('/', function(){
    return 'Hello world';
}, 'PUT');

$route('/', function(){
    return 'Hello world';
}, 'PATCH');

$route('/', function(){
    return 'Hello world';
}, 'DELETE');
```

#### Respond to multiple HTTP verbs

Creating a route that responds to multiple HTTP verbs is extremely easy.

```php
$route('/', function(){
    return 'Hello world';
}, ['GET', 'POST']);
``` 

You can also creat a route that responds to all HTTP verbs.

```php
$route->all('/', function(){
    return 'Hello world';
});
```