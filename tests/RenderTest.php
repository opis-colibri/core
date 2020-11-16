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

namespace Opis\Colibri\Test;

use Opis\Colibri\Testing\Builders\ApplicationBuilder;
use Opis\Colibri\HTML\{Attributes, Link, Meta, Script, Style};
use function Opis\Colibri\render;

class RenderTest extends BaseAppTestCase
{
    protected static function setupApp(ApplicationBuilder $builder): void
    {
        // nop
    }

    public function testAttributes()
    {
        $attributes = new Attributes();
        $attributes->add('foo')
            ->add('bar', 'baz');
        $this->assertEquals(' foo bar="baz"', render($attributes));
    }

    public function testEmptyAttributes()
    {
        $attributes = new Attributes();
        $this->assertEquals('', render($attributes));
    }

    public function testStyle()
    {
        $style = new Style("foo", "bar");
        $expect = PHP_EOL . '<style type="text/css" media="bar">' .
            PHP_EOL . "foo" . PHP_EOL . "</style>";
        $this->assertEquals($expect, render($style));
    }

    public function testInlineScript()
    {
        $script = new Script();
        $script->attribute("type", "text/javascript")
            ->content("alert(1);");
        $expect = PHP_EOL . '<script type="text/javascript">alert(1);</script>';
        $this->assertEquals($expect, render($script));
    }

    public function testNotInlineScript()
    {
        $script = new Script();
        $script->attribute("type", "text/javascript")
            ->src("javascript.js");
        $expect = PHP_EOL . '<script type="text/javascript" src="javascript.js"></script>';
        $this->assertEquals($expect, render($script));
    }

    public function testMeta()
    {
        $meta = new Meta();
        $meta->attribute('name', 'description')
            ->attribute('content', 'foo');
        $expect = PHP_EOL . '<meta name="description" content="foo">';
        $this->assertEquals($expect, render($meta));
    }

    public function testLink()
    {
        $link = new Link();
        $link->attribute('rel', 'stylesheet')
            ->attribute('href', 'style.css');
        $expect = PHP_EOL . '<link rel="stylesheet" href="style.css">';
        $this->assertEquals($expect, render($link));
    }
}