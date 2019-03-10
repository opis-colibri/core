---
layout: project
version: 1.x
title: Routing phases | Routing
description: Learn about the routing process and its phases
---

## First phase

In this phase, the routes are sorted based on their priority, and after the 
sorting process completes, each route is checked if it's a match for the 
request's path URI. If the route is a match, then it is added to the *list of
matched routes*.

After checking each route, if the *list of matched routes* is empty, then
a `404` error is returned with the help of the `Opis\Http\Response` class.
Otherwise, the algorithm advances to the next phase.

## Second phase

At this phase, each route from the *list of matched routes* is taken in turn
and becomes the *current route*. The *current route* is passed through a series of [internal](./routes.html#route-constraints)
filters and also through all user-defined [filters](./routes.html#regular-filters) that were set for this particular route, 
if any. The first route that passes all filters becomes the *candidate route*.

If no *candidate route* was found, then a `404` error is returned. Otherwise, when a *candidate route* is found, 
the remaining routes from the *list of matched routes* are discarded and the algorithm advances to the next phase.

## Third phase

The algorithm checks if there are any callbacks that were set as *[guard filters](./routes.html#guard-filters)* 
to the *candidate route* and execute them in order. If one of the *guard filters* is not passed, then a `404` error 
is returned. Otherwise the algorithm continues with the fourth phase and the *candidate route* becomes the *matched route*.

## Fourth phase

If there are no [middleware](./middleware.html) defined on the *matched route*, the route's callback is 
[invoked](#routes-callback-invocation) and the result is returned as the response to the current HTTP request.

Otherwise, if there are middleware defined on the *matched route*, they are chained together and the first 
middleware in the chain is executed. Each middleware can invoke the next middleware in the chain and use the result 
returned by invoking that middleware as an HTTP response, or it can omit invoking the next middleware and simply 
return a response. The last middleware in the chain will invoke the route's callback.
 
#### Route's callback invocation

Invoking a route's callback consist in resolving all route variables referenced as arguments by the callback, and
use those values as arguments for invoking the callback. The result obtained by invoking the callback is then checked to
see if it's an instance of `Opis\Http\Response` class. If is not, the framework will try to stringify the result and
use that string to construct an instance of `Opis\Http\Responses\HtmlResponse` class, which will be treated as if it 
was the result returned by the callback.

