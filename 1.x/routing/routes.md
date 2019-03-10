---
layout: project
version: 1.x
title: Routes | Routing
description: Learn about routes and how to use them
---

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

Another way of creating a route is through the direct invocation of the collector object.

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

You can use for the route's callback any type of callable values, not only closures. 
Just make sure that the callable value you're using is serializable (closures are serialized with the help 
of the [opis/closure](https://opis.io/closure){: target="_blank"} library). 

```php
// static method
$route('/', Foo::class . '::staticMethod');

// non-static method
$route('/', [new Foo(), 'bar']);

// invokable object
$route('/', new Invokable());
```


### Respond to multiple HTTP verbs

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

### Optional parameters

A route parameter can be marked as being optional by putting the `?` sign after the parameter's name.

```php
// Add a default value for the optional route parameter
$route('/article/{id?}', function($id = 1){
    return $id;
});
```

In the above example, accessing `/article/1` has the same effect as accessing`/article`.

## Route constraints

Route constraints are an effective way of filtering routes. 
The framework support several types of constraints that can be applied simultaneously.

### Regex constraints

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

A regex constraint can be added not only to whole route segments, but also to a subsection of them.
The value of these subsections can be also referenced by the route's callback.

```php
$route('/images/logo.{extension}', function($extension) {
    return $extension
})
->whereIn('extension', ['png', 'jpg', 'svg']);
```

#### Inline regex constraints

There are two types of inline regex constraints: anonymous constraints and named constraints.
Named constraints are just a convenient way of defining constraints for route parameters 
without using the `where` method. 

```php
$route('/article/{id=[0-9]+}', function($id){
    return $id;
});

$route('/user/{name?=[a-z]+}', function($name = 'admin'){
    return $name;
});

// same as

$route('/article/{id}', function($id){
    return $id;
})
->where('id', '[0-9]+');

$route('/user/{name?}', function($name = 'admin'){
    return $name;
})
->where('name', '[a-z]+');
```

As their name suggest, anonymous constraints are just a way of adding regex constraints to a route segment or
to a subsection.

```php
$route('/user/{=[a-z]+}', function(){
    // Matches `/user/foo`, `/user/bar`, etc
});

//optional segment

$route('/user/{?=[a-z]}', function(){
    // Matches `/user`, `/user/foo`, `/user/bar`, etc
});
``` 

### Domain constraints

By using the `domain` method, routes can be constrained to be available only for a specific domain or sub-domain.

```php
$route('/', function(){
    return 'Hello';
})
->domain('example.com');
```

This method will also allow you to capture segments of the domain as parameters. 
These parameters can be then referenced on the route's callback.

```php
$route('/', function($subdomain){
    return $subdomain;
})
->domain('{subdomain}.example.com');
```

Segments can be marked as being optional and regex constraints can be applied to them.

```php
$route('/', function($subdomain = 'www'){
    return $subdomain;
})
->domain('{subdomain?}.example.com')
->whereIn('subdomain', ['www', 'api', 'docs']);
```

### Secure connections

Routes can be constrained to be available only when they are accessed using a secured HTTPS connection, with
the help of the `secure` method.

```php
$route('/', function(){
    return 'Hello';
})
->secure();
```

## Implicit values

Setting an implicit value for an optional parameter is done by using the `implicit` method.

```php

$route('/user/{name?}', function($name){
    return $name;
})
->implicit('name', 'John Doe');
```

You can also use the `implicit` method to declare values that are not defined as route parameters. 
These values ​​can then be used by various callbacks.

```php
$route('/test', function($foo, $bar, $baz){
    return $foo . $bar . $baz;
})
->implicit('foo', 'Foo value')
->implicit('bar', 'Bar value')
->implicit('baz', 'Baz value');
```

### Built-in implicit values

Beside the user-defined implicit values, there are a series of globally available values, 
defined by the framework itself.  

- `$request`: An instance of the `Opis\Http\Request` class, representing the current request.
- `$app`: An instance of the `Opis\Colibri\Application` class.
- `$lang`: A string representing the language in use for the current request. Its default value is `en`.
- `route`: An object representing the current route, which is available starting with 
the second phase of the routing process.

## Bindings

Adding a binding to a route is done by using the `bind` method. 
Each binding has a name and a callback that will be invoked in order to resolve the binding. 
The value obtained by resolving the binding will be considered to be the binding's value.
Just like the implicit values described above, a binding can be referenced by callbacks.
The major difference between implicit values and bindings is given by the fact that the bindings 
are only available starting with the [third phase](./#third-phase) of the routing process.

```php
$route('/', function(string $foo){
    return $foo;
})
->bind('foo', function(){
    return 'Hello, World!';
});
```

A binding is resolved only when is referenced by a callback that it's about to be invoked. 
If a binding is not referenced in any callback at all, or if the callback is not going to be invoked, 
then the binding will not be resolved.

```php
$route('/', function($foo){
    return $foo;
})

// this binding will be resolved 
// because $foo is referenced in the route's callback
->bind('foo', function(){
    return 'foo';
})

// this binding will not be resolved
// since there is no reference to $bar in any callbacks at all
->bind('bar', function($baz){
    return 'bar';
})

// even though there is a reference to $baz
// this binding will not be resolved because the callback
// referencing $baz will never be invoked
->bind('baz', function(){
    return 'baz';
});
```

A binding can be used to define new route variables, or tho overwrite the value of existing 
route variables like route parameters, domain parameters, implicit values, and even other bindings.

```php
$route('/user/{name}', function($foo){
    return $foo;
})
// reference $name route parameter
// and use it to define $foo route variable 
->bind('foo', function($name){
    return 'foo-' . $name;
});

// change the value of $name
$route('/user/{name}', function($name){
    return $name;
})
->bind('name', function($name){
    return strtoupper($name);
});
```

## Filters

There are some situations when just adding various constraints to your routes isn't enough to properly filter them.
In order to help you overcome this kind of situations, the framework allows you to define custom filters in the
form of callbacks.

### Callbacks

Defining a callback is done with the help of the `callback` method.
This method takes as arguments the name of the callback and a callable value that will be invoked when
the filter is evaluated. The callable must return a boolean value.

```php
$route('/', function(){
    // do something
})
->callback('foo', function(){
   return true; 
});
```

You can define as many callbacks as you want, but they won't be invoked unless they are explicitly 
used in the filtering process.

```php
// None of the callbacks defined here will be invoked
$route('/', function(){
    // do something
})
->callback('foo', function(){
   return true; 
})
->callback('bar', function(){
   return true; 
})
->callback('baz', function(){
   return true; 
});
```

Callbacks can reference all kind route variables, depending on the type of the filter that is using them.

```php
$route('/user/{name}', function($name){
    return $name;
})
->callback('is_admin', function($name){
    return $name === 'admin';
});
```

### Regular filters

These filters are applied in the [second phase](./#second-phase) of the routing process and are defined with the help 
of the `filter` method. The method takes as arguments a series of one or more callback names.

```php
$route('/user/{name}', function($name){
    return $name;
})
->callback('is_admin', function($name){
    return $name === 'admin';
})
->filter('is_admin');

$route('/', function(){
    // do something
})
->callback('foo', function(){
   return true; 
})
->callback('bar', function(){
   return true; 
})
->callback('baz', function(){
   return true; 
})
->filter('foo', 'bar', 'baz');
```

### Guard filters

This kind of filters are applied in the [third phase](./#third-phase) of the routing process, and in contrast
to regular filters they can reference bindings. Applying guard filters is done with the help of the `guard` method.
The method takes as arguments a series of one or more callback names.

```php
$route('/', function(){
    // do something
})
->callback('foo', function(){
   return true; 
})
->callback('bar', function(){
   return true; 
})
->callback('baz', function(){
   return true; 
})
->guard('foo', 'bar', 'baz');
```

To better exemplify their use case, let's take the following example and explain it.

```php
$route('/user/{id}', function(User $user){
    return $user->name();
})
->where('id', '[1-9][0-9]*')
->bind('user', function($id){
    return entity(User::class)->find($id);
});
```

The route defined above will match any `GET` request of which the URI path has the following form: 
`/user/1`, `/user/29`, `/user/113`, etc. The `user` binding is defined with the help of the `id` route parameter and is
finally used in the route's callback. The binding is resolved to an instance of the `User` entity by trying to load
that entity by its ID with the help of the `find` method. The problem occurs when no entity that has the specified ID
is found and the binding is resolved to `null`. To overcome this situation, a guard filter is  exactly what we need.

```php
$route('/user/{id}', function(User $user){
    return $user->name();
})
->where('id', '[1-9][0-9]*')
->bind('user', function($id){
    return entity(User::class)->find($id);
})
->callback('user_exists', function($user){
    return $user !== null;
})
->guard('user_exists');
```

