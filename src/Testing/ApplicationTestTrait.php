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

namespace Opis\Colibri\Testing;

use Opis\Http\{Request, Response};
use Opis\Colibri\Testing\Builders\ApplicationBuilder;
use Symfony\Component\Console\Input\ArrayInput;

trait ApplicationTestTrait
{
    protected static ?Application $app = null;

    /** @var callable|null */
    protected static $onAppDestroy = null;

    protected static function buildApp(): void
    {
        if (static::$app) {
            static::destroyApp();
        }

        $builder = new ApplicationBuilder(static::vendorDir(), static::rootDir());

        static::setupApp($builder);

        $app = $builder->build();

        static::$onAppDestroy = static fn() => $builder->destroy($app);

        static::$app = $app;
    }

    protected static function destroyApp(): void
    {
        if (static::$onAppDestroy !== null) {
            (static::$onAppDestroy)();
        }
        static::$onAppDestroy = null;
        static::$app = null;
    }

    protected static function rootDir(): ?string
    {
        return null;
    }

    abstract protected static function vendorDir(): string;

    abstract protected static function setupApp(ApplicationBuilder $builder): void;

    // -----------------

    protected function app(): Application
    {
        return static::$app;
    }

    protected function exec(string $path, string $method = 'GET', array $headers = [], bool $secure = false): Response
    {
        $request = new Request($method, $path, 'HTTP/1.1', $secure, $headers);
        return $this->execRequest($request);
    }

    protected function execGET(string $path, array $query = [], array $headers = [], bool $secure = false): Response
    {
        $request = new Request('GET', $path, 'HTTP/1.1', $secure, $headers, [], null, [], $query);
        return $this->execRequest($request);
    }

    protected function execPOST(string $path, array $data = [], array $headers = [], bool $secure = false): Response
    {
        $request = new Request('POST', $path, 'HTTP/1.1', $secure, $headers, [], null, [], null, $data);
        return $this->execRequest($request);
    }

    protected function execRequest(Request $request, bool $clearCache = true): Response
    {
        $response = $this->app()->run($request, false);
        if ($clearCache) {
            $this->app()->getCache()->clear();
        }
        return $response;
    }

    protected function execCommand(string ...$command): int
    {
        return $this->app()->getConsole()->run(new ArrayInput($command));
    }
}