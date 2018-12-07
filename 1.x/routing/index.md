---
layout: project
version: 1.x
title: The basics | Routing
description: Learn how to collect routes
---
# The basics

* [Collecting routes](#collecting-routes)
* [Creating routes](#creating-routes)
* [Route parameters](#route-parameters)


## Collecting routes

Routes are collected with the help of a collector named `routes`, which is represented 
by the `Opis\Colibri\ItemCollectors\RouteCollector` class. 

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
        // routes are defined here
    }
}
```

## Creating routes

Creating a route is simply a matter of defining an URI path and an associated callback that will be invoked
when the route is executed.

```php
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

## Route parameters

You can capture segments of your route's path by defining route parameters. 
All route parameters can be referenced on the route's callback.

```php
$route('/article/{id}', function($id){
    return $id;
});

$route('/article/{id}/edit', function($id){
    return $id;
});
```

A path can contain multiple route parameters and they can be referenced in any order you want.

```php
$route('/blog/{id}/preview/{article}', function($article, $id){
    return $id . ':' . $article;
});

// The order on which route parameters are referenced is irrelevant

$route('/blog/{id}/preview/{article}', function($id, $article){
    return $id . ':' . $article;
});
```

#### Optional parameters

A route parameter can be marked as being optional by putting the `?` sign after the parameter's name.

```php
// Add a default value for the optional route parameter
$route('/article/{id?}', function($id = 1){
    return $id;
});
```

In the above example, accessing `/article/1` has the same effect as accessing`/article`.

#### Regex constraints