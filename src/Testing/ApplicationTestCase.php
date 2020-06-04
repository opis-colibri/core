<?php
/* ============================================================================
 * Copyright 2018 Zindex Software
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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Opis\Http\{Request, Response};
use Opis\Colibri\Testing\Builders\{ApplicationBuilder, AppInitBuilder};

abstract class ApplicationTestCase extends TestCase
{

    protected static ?Application $app = null;

    /** @var null|callable */
    protected static $onAppDestroy = null;

    /**
     * @return string
     */
    abstract protected static function vendorDir(): string;

    /**
     * @return string
     */
    protected static function rootDir(): ?string
    {
        return null;
    }

    /**
     * @param ApplicationBuilder $builder
     * @return void
     */
    abstract protected static function applicationSetup(ApplicationBuilder $builder);

    /**
     * @param ApplicationBuilder $builder
     * @return string[]
     */
    protected static function applicationDependencies(/** @noinspection PhpUnusedParameterInspection */ApplicationBuilder $builder): array
    {
        return [];
    }

    /**
     * @return null|AppInitBuilder
     */
    protected static function bootstrapBuilder(): ?AppInitBuilder
    {
        return null;
    }

    /**
     * @param string $vendorDir
     * @param null|string $rootDir
     * @param null|AppInitBuilder $builder
     * @return ApplicationBuilder
     */
    protected static function applicationBuilder(string $vendorDir, ?string $rootDir, ?AppInitBuilder $builder): ApplicationBuilder
    {
        return new ApplicationBuilder($vendorDir, $rootDir, $builder);
    }

    /**
     * @param Application $app
     */
    protected static function applicationStarted(Application $app)
    {
        // Nothing to do
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass(): void
    {
        $builder = static::applicationBuilder(static::vendorDir(), static::rootDir(), static::bootstrapBuilder());

        $builder->addDependencies(...static::applicationDependencies($builder));

        static::applicationSetup($builder);

        $app = $builder->build();

        static::applicationStarted($app);

        static::$onAppDestroy = function () use ($builder, $app) {
            $builder->destroy($app);
        };

        static::$app = $app;
    }

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        if (static::$onAppDestroy !== null) {
            (static::$onAppDestroy)();
        }
        static::$onAppDestroy = null;
        static::$app = null;
    }

    /**
     * @return Application
     */
    protected function app(): Application
    {
        return static::$app;
    }

    /**
     * @param string $path
     * @param string $method
     * @param array $headers
     * @param bool $secure
     * @return Response
     */
    protected function exec(string $path, string $method = 'GET', array $headers = [], bool $secure = false): Response
    {
        $request = new Request($method, $path, 'HTTP/1.1', $secure, $headers);
        return $this->execRequest($request);
    }

    /**
     * @param string $path
     * @param array $query
     * @param array $headers
     * @param bool $secure
     * @return Response
     */
    protected function execGET(string $path, array $query = [], array $headers = [], bool $secure = false): Response
    {
        $request = new Request('GET', $path, 'HTTP/1.1', $secure, $headers, [], null, [], $query);
        return $this->execRequest($request);
    }

    /**
     * @param string $path
     * @param array $data
     * @param array $headers
     * @param bool $secure
     * @return Response
     */
    protected function execPOST(string $path, array $data = [], array $headers = [], bool $secure = false): Response
    {
        $request = new Request('POST', $path, 'HTTP/1.1', $secure, $headers, [], null, [], null, $data);
        return $this->execRequest($request);
    }

    /**
     * @param Request $request
     * @param bool $clearCache
     * @return Response
     */
    protected function execRequest(Request $request, bool $clearCache = true): Response
    {
        $response = $this->app()->run($request);
        if ($clearCache) {
            $this->app()->getCache()->clear();
        }
        return $response;
    }

    /**
     * @param string ...$command
     * @return int
     * @throws \Exception
     */
    protected function execCommand(string ...$command): int
    {
        return $this->app()->getConsole()->run(new ArrayInput($command));
    }
}