<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri\Serializable;

use Serializable;
use Opis\Colibri\Application;

class Translations implements Serializable
{
    protected $app;
    protected $translations = array();
    protected $oldTranslations = array();

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function translate($language, array $sentences)
    {
        if (!isset($this->translations[$language])) {
            $this->translations[$language] = array();
        }

        $this->translations[$language] += $sentences;
    }

    public function serialize()
    {
        $storage = $this->app->translations();

        foreach ($this->oldTranslations as $translation) {
            $storage->delete($translation);
        }

        $this->oldTranslations = array_keys($this->translations);

        foreach ($this->oldTranslations as $translation) {
            $storage->write($translation, $this->translations[$translation]);
        }

        return serialize($this->oldTranslations);
    }

    public function unserialize($data)
    {
        $this->oldTranslations = unserialize($data);
    }
}
