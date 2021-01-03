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

namespace Opis\Colibri\I18n;

use Closure;

class Plural
{
    protected int $forms;
    protected string $rule;
    /** @var callable|null */
    protected $func = null;

    /**
     * @param int $forms
     * @param string $rule
     * @param callable|null $func
     */
    public function __construct(int $forms, string $rule, ?callable $func = null)
    {
        $this->forms = $forms;
        $this->rule = $rule;
        $this->func = $func;
    }

    /**
     * @inheritDoc
     */
    public function forms(): int
    {
        return $this->forms;
    }

    /**
     * @inheritDoc
     */
    public function form(int $count): int
    {
        if ($this->func === null) {
            $this->func = $this->parseRule($this->rule, $this->forms);
        }

        /** @var callable $f */
        $f = $this->func;
        $count = (int)$f($count);
        if ($count < 0 || $count >= $this->forms) {
            $count = $this->forms == 1 ? 0 : 1;
        }

        return $count;
    }

    /**
     * @inheritDoc
     */
    public function rule(): string
    {
        return $this->rule;
    }

    /**
     * @param string $rule
     * @param int $forms
     * @return callable
     */
    protected function parseRule(string $rule, int $forms = 2): callable
    {
        if (!static::ruleIsValid($rule)) {
            $rule = null;
        } else {
            $rule = str_replace('n', '$n', static::fixRuleTernary($rule));
            $rule = "return static fn (int \$n): int => (int)($rule);";
            $rule = eval($rule);
            if (!($rule instanceof Closure)) {
                $rule = null;
            }
        }

        if ($rule === null) {
            $rule = $forms === 1
                ? static fn(int $count): int => 0
                : static fn(int $count): int => $count === 1 ? 0 : 1;
        }

        return $rule;
    }

    /**
     * Checks if the rule syntax is valid
     * @param string $rule
     * @return bool
     */
    public static function ruleIsValid(string $rule): bool
    {
        // Invalid n position
        if (preg_match('~n\s*(\(|n)~m', $rule)) {
            return false;
        }

        // Check allowed tokens
        return (bool)preg_match('~^(?:n|[0-9]|\s|\(|\)|(?:\<|\>)\=?|(?:\!|\=)\=|\%|\?|\:|(?:\|\|)|(?:\&\&))+$~m', $rule);
    }

    /**
     * Fix ternary operator to be a correct PHP expression
     * @param string $rule
     * @return string
     */
    public static function fixRuleTernary(string $rule): string
    {
        if (!str_contains($rule, ':')) {
            return $rule;
        }

        // Get every "else"
        $parts = explode(':', $rule);

        // Add parentheses
        $rule = array_pop($parts);
        while (count($parts) > 0) {
            $rule = array_pop($parts) . ':(' . $rule . ')';
        }

        // Remove parentheses around numbers
        return preg_replace('~\((\d)\)~', '$1', $rule);
    }

    /**
     * Arrange rule
     * @param string $rule
     * @return string
     */
    public static function beautifyRule(string $rule): string
    {
        $rule = str_replace(' ', '', $rule);

        return strtr($rule, [
            '&&' => ' && ',
            '||' => ' || ',
            '==' => ' == ',
            '!=' => ' != ',
            '<=' => ' <= ',
            '>=' => ' >= ',
            '<'  => ' < ',
            '>'  => ' > ',
            '?'  => ' ? ',
            ':'  => ' : ',
            '%'  => ' % ',
        ]);
    }

    protected static function pluralsDir(): string
    {
        return __DIR__ . '/../../plurals';
    }

    public static function create(string $locale): self
    {
        $locale = Locale::parse($locale);

        $lang = $locale['language'];
        $files = [$lang, 'en'];

        if (isset($locale['region']) ?? $locale['region']) {
            array_unshift($files, $lang . '_' . $locale['region']);
        }

        $dir = rtrim(static::pluralsDir(), '/');

        foreach ($files as $file) {
            $file = "{$dir}/{$file}.php";

            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                $file = include($file);
                if (!is_array($file)) {
                    continue;
                }

                return static::fromArray($file);
            }
        }

        return static::fromArray([]);
    }

    public static function fromArray(array $plural): self
    {
        return new static(
            $plural['forms'] ?? 2,
            $plural['rule'] ?? '(n != 1)',
            $plural['func'] ?? null
        );
    }
}
