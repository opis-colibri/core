<?php
/* ===========================================================================
 * Copyright 2018-2021 Zindex Software
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

use Opis\Colibri\I18n\Translator;

class SubTranslator
{
    protected string $ns;
    protected Translator $translator;
    protected int $count = 1;
    protected ?string $context = null;
    protected string|LanguageInfo|null $language = null;
    protected array $params = [];

    public function __construct(Translator $translator, string $ns)
    {
        $this->ns = $ns;
        $this->translator = $translator;
    }

    public function ns(): string
    {
        return $this->ns;
    }

    public function translator(): Translator
    {
        return $this->translator;
    }

    public function reset(): self
    {
        $this->count = 1;
        $this->context = null;
        $this->params = [];
        $this->language = null;

        return $this;
    }

    public function count(int $count): self
    {
        $this->count = $count;
        return $this;
    }

    public function context(?string $context = null): self
    {
        $this->context = $context;
        return $this;
    }

    public function params(array $params): self
    {
        $this->params = $params;
        return $this;
    }

    public function language(string|LanguageInfo|null $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function translate(string $key): string
    {
        return $this->translator->translate(
            $this->ns,
            $key,
            $this->context,
            $this->params,
            $this->count,
            $this->language,
        );
    }
}