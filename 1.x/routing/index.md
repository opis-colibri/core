---
layout: project
version: 1.x
title: Routing phases | Routing
description: Learn about the routing process and its phases
---
# Routing phases

## First phase

In this phase, the routes are sorted based on their priority, and after the 
sorting process completes, each route is checked if it's a match for the 
request's path URI. If the route is a match, then it is added into the *queue of
matched routes*.

After checking each route, if the queue of matched routes is empty, then
a `404` error is returned with the help of the `Opis\Http\Response` class.
Otherwise, the algorithm continue to the next phase.

## Second phase

At this phase, each route from the *queue of matched routes* is taken in turn
and it becomes the *current route*. The *current route* is passed through a series of internal 
filters and also through all user-defined filters that was set for this particular route, if any. 
The first route that passes all filters becomes the *candidate route*.

If no *candidate route* was found, then a `404` error is returned. Otherwise, when a *candidate route* is found, the 
algorithm continues with the next phase, discarding all the remaining routes from the *queue of matched routes*.

## Third phase

This is the phase when binding takes place. 

## Fourth phase