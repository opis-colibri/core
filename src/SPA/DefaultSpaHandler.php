<?php
/* ===========================================================================
 * Copyright 2014-2018 The Opis Project
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

namespace Opis\Colibri\SPA;

class DefaultSpaHandler extends SpaHandler
{
    /** @var string[] */
    private $packages = [];

    /**
     * @inheritDoc
     */
    public function importPackage(string $package, array $conf = null)
    {
        $this->packages[] = "import '$package';";
    }

    /**
     * @inheritDoc
     */
    public function prepare()
    {
        $content = implode(PHP_EOL, $this->packages);
        $dir = $this->spa->dir();
        $tpl = $dir . DIRECTORY_SEPARATOR . 'index.tpl.js';
        if (file_exists($tpl)) {
           $content = str_replace('{{import}}', $content, file_get_contents($tpl));
        }
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'index.js', $content);
    }
}