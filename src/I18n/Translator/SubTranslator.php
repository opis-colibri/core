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

namespace Opis\Colibri\I18n\Translator;

class SubTranslator
{

    protected string $ns;

    protected Translator $translator;

    protected int $count = 1;

    protected ?string $context = null;

    protected array $params = [];

    /**
     * SubTranslator constructor.
     * @param Translator $translator
     * @param string $ns
     */
    public function __construct(Translator $translator, string $ns)
    {
        $this->ns = $ns;
        $this->translator = $translator;
    }

    /**
     * @return SubTranslator
     */
    public function reset(): self
    {
        $this->count = 1;
        $this->context = null;
        $this->params = [];

        return $this;
    }

    /**
     * @param int $count
     * @return SubTranslator
     */
    public function count(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @param string|null $context
     * @return SubTranslator
     */
    public function context(?string $context = null): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * @param array $params
     * @return SubTranslator
     */
    public function params(array $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param string $key
     * @param string|LanguageInfo|null $lang
     * @return string
     */
    public function translate(string $key, $lang = null)
    {
        return $this->translator->translate(
            $this->ns,
            $key,
            $this->context,
            $this->params,
            $this->count,
            $lang
        );
    }

    /**
     * @param string $key
     * @param array|null $params
     * @param int $count
     * @param string|null $context
     * @param string|null|LanguageInfo $lang
     * @return string
     */
    public function t(string $key, ?string $context = null, ?array $params = null, int $count = 1, $lang = null)
    {
        $this->params = $params ?? [];
        $this->count = $count;
        $this->context = $context;

        return $this->translator->translate(
            $this->ns,
            $key,
            $context,
            $params,
            $this->count,
            $lang
        );
    }
}