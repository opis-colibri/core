<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Opis\Colibri\Test\Render;

use Opis\Colibri\Render\Renderer;
use PHPUnit\Framework\TestCase;

class ViewsTest extends TestCase
{
    protected Renderer $renderer;

    public function setUp(): void
    {
        $this->renderer = new Renderer();
    }

    public function testResolve()
    {
        $this->renderer->handle('foo', static fn() => 'bar');

        $this->assertEquals('bar', $this->renderer->resolveViewName('foo'));
    }

    public function testResolveMissing()
    {
        $this->assertEquals(null, $this->renderer->resolveViewName('missing'));
    }

    public function testResolveMultiple()
    {
        $this->renderer->handle('foo', static fn() => 'bar');

        $this->renderer->handle('foo', static fn() => 'baz');

        $this->renderer->handle('foo', static fn() => 'qux');

        $this->assertEquals('qux', $this->renderer->resolveViewName('foo'));
    }

    public function testResolvePriority()
    {
        $this->renderer->handle('foo', static fn() => 'bar', 1);

        $this->renderer->handle('foo', static fn() => 'baz');

        $this->assertEquals('bar', $this->renderer->resolveViewName('foo'));
    }

    public function testImplicitValues()
    {
        $this->renderer->handle('foo.{bar?}', static fn($bar) => $bar)->default('bar', 'baz');
        $this->assertEquals('baz', $this->renderer->resolveViewName('foo'));
    }

    public function testEngine()
    {
        $this->renderer->getEngineResolver()->register(static fn() => new RenderEngine1());

        $this->renderer->handle('foo', static fn() => 'bar');

        $this->assertEquals('BAR', $this->renderer->renderView('foo'));
    }

    public function testEnginePriority1()
    {
        $this->renderer->getEngineResolver()->register(static fn() => new RenderEngine1());

        $this->renderer->getEngineResolver()->register(static fn() => new RenderEngine2());

        $this->renderer->handle('foo', static fn() => 'bar');

        $this->assertEquals('BAR!', $this->renderer->renderView('foo'));
    }

    public function testEnginePriority2()
    {
        $this->renderer->getEngineResolver()->register(static fn() => new RenderEngine1(), 1);

        $this->renderer->getEngineResolver()->register(static fn() => new RenderEngine2());

        $this->renderer->handle('foo', static fn() => 'bar');

        $this->assertEquals('BAR', $this->renderer->renderView('foo'));
    }

    public function testSerialization()
    {
        \Opis\Closure\Library::init();

        $this->renderer->getEngineResolver()->register(static fn() => new RenderEngine1(), 1);

        $this->renderer->getEngineResolver()->register(static fn() => new RenderEngine2());

        $this->renderer->handle('foo', static fn() => 'bar');

        $renderer = unserialize(serialize($this->renderer));

        $this->assertEquals('BAR', $renderer->renderView('foo'));
    }
}
