---
layout: project
version: 1.x
title: The Basics | Routing
description: Learn how to collect routes
---
# The Basics

* [Collecting routes](#collecting-routes)
* [Creating routes](#creating-routes)
* [Route parameters](#route-parameters)
* [Implicit values](#implicit-values)

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

Adding a regex constraint that targets a specific route parameter is done by using the `where` method. The method
takes as arguments the parameter's name and a regular expression.

```php
$route('/article/{id}', function($id){
    return $id;
})
->where('id', '[0-9]+');
```

Constraining a parameter to a list of values is done by using the `whereIn` method.

```php
$route('/article/{id}/{action}', function($id, $action){
    return $action . ':' . $id;
})
->where('id', '[0-9]+')
->whereIn('action', ['edit', 'delete', 'foo.bar']);

// equivalent of..

$route('/article/{id}/{action}', function($id, $action){
    return $action . ':' . $id;
})
->where('id', '[0-9]+')
->where('action', 'edit|delete|foo\.bar');
```

## Implicit values

Setting an implicit value for an optional parameter is done by using the `implicit` method.

```php

$route('/user/{name?}', function($name){
    return $name;
})
->implicit('name', 'John Doe');
```

You can also use the `implicit` method to declare values that are not defined as route parameters. These values
can also be referenced by the route's callback.

```php
$route('/test', function($foo, $bar, $baz){
    return $foo . $bar . $baz;
})
->implicit('foo', 'Foo value')
->implicit('bar', 'Bar value')
->implicit('baz', 'Baz value');
```
