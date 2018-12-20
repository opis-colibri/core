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
request's path URI. If the route is a match, then its added to the queue of
matched routes.

After checking each route, if the queue of matched routes is empty, then
a `404` error is returned with the help of the `Opis\Http\Response` class.
Otherwise, the algorithm continue to the next phase.

## Second phase