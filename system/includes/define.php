<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

namespace Colibri\Define;

use Closure;
use Opis\Colibri\App;

function Routes(Closure $callback, $priority = 0)
{
    App::collector()->handle('routes', $callback, $priority);
}

function RouteAliases(Closure $callback, $priority = 0)
{
    App::collector()->handle('aliases', $callback, $priority);
}

function Dispatchers(Closure $callback, $priority = 0)
{
    App::collector()->handle('dispatchers', $callback, $priority);
}

function Views(Closure $callback, $priority = 0)
{
    App::collector()->handle('views', $callback, $priority);
}

function ViewEngines(Closure $callback, $priority = 0)
{
    App::collector()->handle('viewEngines', $callback, $priority);
}

function Contracts(Closure $callback, $priority = 0)
{
    App::collector()->handle('contracts', $callback, $priority);
}

function Connections(Closure $callback, $priority = 0)
{
    App::collector()->handle('connections', $callback, $priority);
}

function Handlers(Closure $callback, $priority = 0)
{
    App::collector()->handle('events', $callback, $priority);
}

function ConfigStorages(Closure $callback, $priority = 0)
{
    App::collector()->handle('configStorages', $callback, $priority);
}

function CacheStorages(Closure $callback, $priority = 0)
{
    App::collector()->handle('cacheStorages', $callback, $priority);
}

function SessionStorages(Closure $callback, $priority = 0)
{
    App::collector()->handle('sessionStorages', $callback, $priority);
}
