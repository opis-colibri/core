---
layout: project
version: 1.x
title: Middleware | Routing
description: Learn about the middleware and how to use them
---

An HTTP middleware is an extremely useful mechanism that can be used to perform a variety of tasks, like
checking if a user is authenticated before invoking the route's callback, or altering the response received form 
the route's callback. This mechanism is applied in the [fourth phase](./#fourth-phase) of the routing process.

## Creating a middleware

Middleware are created in the form of a class that extends the `Opis\Colibri\Routing\Middleware` abstract class.
The middleware class must define a public `__invoke` method that will be called when the middleware is invoked.

```php
namespace Vendor\Module;

use Opis\Colibri\Routing\Middleware;

class MyMiddleware extends Middleware
{
    public function __invoke()
    {
        // middleware logic
    }
}
```

A middleware can either directly return a response or it can invoke the next middleware in the chain and return
whatever that middleware returns. Invoking the next middleware in the chain is done by using the protected `next`
method. This method will always return an instance of `Opis\Http\Response`.

```php
class MyMiddleware extends Middleware
{
    public function __invoke()
    {
        if (rand(0, 100) % 2 === 0) {
            return "My own response";
        }
        
        // invoke the next middleware in the chain
        // and return whatever it returns
        return $this->next();
    }
}
```

## Applying a middleware

Middleware are applied directly to routes in the moment of their creation with the help of the `middleware` method.

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
        // apply a middleare
        $route('/', function(){
            return 'Hello, World!';
        })
        ->middleware(MyMiddleware::class);
    }
}
```

You can also apply multiple middleware, making a chain of middleware.

```php
$route('/', function(){
    return 'Hello, World!';
})
->middleware(Foo::class, Bar::class, Baz::class);
```

## Referencing route variables

A middleware can reference any kind of route variable, from bindings to implicit values or to route parameters.

{% capture tab_id %}{% increment tab_id %}{% endcapture %}
{% capture tabs %}
{% capture middleware %}
```php
namespace Vendor\Module;

use Opis\Colibri\Routing\Middleware;

class MyMiddleware extends Middleware
{
    public function __invoke(string $name)
    {
        if ($name[0] === 'a') {
            return strtoupper($name);
        }
        
        return $this->next();
    }
}
```
{% endcapture %}
{% capture collector%}
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
        $route('/user/{name}', function(string $name){
            return $name;
        })
        ->where('name', '[a-z]+')
        ->middleware(MyMiddleware::class);
    }
}
```
{% endcapture %}
{% include tab.html id=tab_id title='Middleware' content=middleware checked=true %}
{% include tab.html id=tab_id title='Collector' content=collector %}
{% endcapture %}
{% include tabs.html content=tabs %}
