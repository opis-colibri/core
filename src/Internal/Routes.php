<?php
/* ===========================================================================
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

namespace Opis\Colibri\Internal;

use Opis\Colibri\Http\Responses\FileStream;
use function Opis\Colibri\{view, env};

/**
 * @internal
 */
final class Routes
{
    private function __construct()
    {
        // only static methods
    }

    public static function welcome()
    {
        return view('welcome');
    }

    public static function file(string $file): FileStream
    {
        return new FileStream(__DIR__ . '/../../resources/assets/' . $file);
    }

    public static function filter(): bool
    {
        return env('APP_PRODUCTION', false) === false;
    }
}