<?php
/* ============================================================================
 * Copyright 2020 Zindex Software
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

namespace Opis\Colibri\RestAPI;

use stdClass;
use Opis\Http\Request;
use function Opis\Colibri\controller;

abstract class ControllerResolver
{
    /**
     * @return callable
     */
    public static function apiController(): callable
    {
        return controller('@controller', '@action');
    }

    /**
     * @param Request $request
     * @return stdClass
     */
    public static function bindData(Request $request): stdClass
    {
        $body = $request->getBody();

        if (!$body) {
            return new stdClass();
        }

        $body = (string)$body;

        if ($body === '') {
            return new stdClass();
        }

        $body = json_decode($body, false);

        if (!is_object($body)) {
            return new stdClass();
        }

        return $body;
    }

    /**
     * @param string $controller
     * @return string
     */
    public static function bindController(string $controller): string
    {
        return static::getControllerMap()[$controller];
    }

    /**
     * @param Request $request
     * @param string $controller Controller class
     * @param string|null $actionScope
     * @return string
     */
    public static function bindAction(Request $request, string $controller, string $actionScope = null): string
    {
        $config = static::getControllerConfig()[$controller] ?? null;

        if (!$config) {
            return 'http404';
        }

        $config = $config['actions'][$actionScope ?? 'default'] ?? null;

        if (!$config) {
            return 'http404';
        }

        $httpMethod = strtolower($request->getMethod());

        return $config[$httpMethod] ?? 'http405';
    }

    /**
     * @return string[]
     */
    abstract protected static function getControllerMap(): array;

    /**
     * @return array
     */
    abstract protected static function getControllerConfig(): array;
}