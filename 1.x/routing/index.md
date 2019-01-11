---
layout: project
version: 1.x
title: Routing phases | Routing
description: Learn about the routing process and its phases
---
# Routing phases

* [First phase](#first-phase)
* [Second phase](#second-phase)
* [Third phase](#third-phase)
* [Fourth phase](#fourth-phase)

## First phase

In this phase, the routes are sorted based on their priority, and after the 
sorting process completes, each route is checked if it's a match for the 
request's path URI. If the route is a match, then it is added into the *queue of
matched routes*.

After checking each route, if the queue of matched routes is empty, then
a `404` error is returned with the help of the `Opis\Http\Response` class.
Otherwise, the algorithm advances to the next phase.

## Second phase

At this phase, each route from the *queue of matched routes* is taken in turn
and becomes the *current route*. The *current route* is passed through a series of internal 
filters and also through all user-defined filters that were set for this particular route, if any. 
The first route that passes all filters becomes the *candidate route*.

If no *candidate route* was found, then a `404` error is returned. Otherwise, when a *candidate route* is found, 
the remaining routes from the *queue of matched routes* are discarded and the algorithm advances to the next phase.

## Third phase

The algorithm checks if there are callbacks that were set as *guard filters* to the *candidate route* and 
execute them in turn. If one of the *guard filters* is not passed, then a `404` error is returned. Otherwise
the algorithm continues with the fourth phase and the *candidate route* becomes the *matched route*.

## Fourth phase

If there are no middleware defined on the *matched route*, the route parameters are resolved and the route's callback
is invoked using the resolved parameters as arguments. The result obtaining by invoking the route's callback is
returned as the response to the HTTP request. If the result is not an instance of th `Opis\Http\Response` class, 
then the algorithm tries to stringify the response and use that string to construct an instance 
of `Opis\Http\Responses\HtmlResponse` which will be returned as the response to the HTTP request. 
 
Otherwise, if there are middleware defined on the *matched route*, they are chained together and the first 
middleware in the chain is executed. Each middleware can invoke the next middleware in the chain 
and use the result returned by invoking that middleware as an HTTP response or it can omit invoking the next middleware
and simply return a response. When the last middleware in the chain try to invoke the next middleware in the chain,
the callback's route is invoked insted.