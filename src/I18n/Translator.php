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

namespace Opis\Colibri\I18n;

use Opis\Colibri\I18n\Translator\{
    Driver, LanguageInfo, SubTranslator
};

abstract class Translator
{
    protected string $defaultLanguage;
    protected Driver $driver;
    /** @var LanguageInfo[]|null */
    protected ?array $languages = null;
    /** @var LanguageInfo[] */
    protected array $localeLanguages = [];
    protected ?array $cache = null;

    /**
     * Translator constructor.
     * @param Driver $driver
     * @param string|null $default_language
     */
    public function __construct(Driver $driver, ?string $default_language = null)
    {
        $this->defaultLanguage = $default_language ?? Locale::SYSTEM_LOCALE;
        $this->driver = $driver;
    }

    public function setDefaultLanguage(string $language): self
    {
        $this->defaultLanguage = $language;
        return $this;
    }

    public function getDefaultLanguage(): string
    {
        return $this->defaultLanguage;
    }

    public function setDriver(Driver $driver): self
    {
        if ($this->driver !== $driver) {
            $this->cache = null;
            $this->languages = null;
            $this->driver = $driver;
        }

        return $this;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }

    public function language(?string $language = null): LanguageInfo
    {
        // Get default language
        if ($language === null) {
            $language = $this->defaultLanguage;
        }

        // Preload languages
        $this->preloadAvailableLanguages();

        if (!array_key_exists($language, $this->languages)) {
            // $language = Locale::canonicalize($language);
            // Create a new language info and do not cache it
            if (!isset($this->localeLanguages[$language])) {
                $this->localeLanguages[$language] = LanguageInfo::create($language);
            }

            return $this->localeLanguages[$language];
        }

        if (!isset($this->languages[$language])) {
            // Load language info
            $info = $this->getDriver()->loadLanguage($language);
            if (is_array($info)) {
                $this->languages[$language] = LanguageInfo::fromArray($info);
            } else {
                if ($info === null) {
                    $info = $language;
                }
                $this->languages[$language] = LanguageInfo::create($info);
            }
            unset($info);
        }

        return $this->languages[$language];
    }

    public function subTranslator(string $ns): SubTranslator
    {
        return new SubTranslator($this, $ns);
    }

    public function translate(
        string $ns,
        string $key,
        ?string $context = null,
        array $params = [],
        int $count = 1,
        string|LanguageInfo|null $language = null
    ): string {

        // Load language
        if (!($language instanceof LanguageInfo)) {
            $language = $this->language($language ?? $this->defaultLanguage);
        }

        // Get translation text
        $text = $this->getTranslationText($language, $ns, $key, $context, $count);

        if ($text === null) {
            // Unavailable translation key
            return $this->getFallbackTranslation($ns, $key, $context);
        }

        // Add count
        if (!isset($params['count'])) {
            $params['count'] = $count;
        }

        // Format result
        return $this->format($text, $params, $language);
    }

    public function translateKey(
        string $key,
        array $params = [],
        int $count = 1,
        string|LanguageInfo|null $language = null
    ): string {
        if (!str_contains($key, ':')) {
            return $key;
        }

        [$ns, $key] = explode(':', $key, 2);

        if (!str_contains($key, '_')) {
            $context = null;
        } else {
            [$key, $context] = explode('_', $key, 2);
        }

        return $this->translate($ns, $key, $context, $params, $count, $language);
    }

    /**
     * Pre-loads language array
     */
    protected function preloadAvailableLanguages(): void
    {
        if ($this->languages !== null) {
            return;
        }

        $this->languages = [];
        $this->languages[Locale::SYSTEM_LOCALE] = null;

        foreach ($this->getDriver()->listLanguages() as $lang) {
            $this->languages[$lang] = null;
        }
    }

    protected function loadNS(string $language, string $ns): ?array
    {
        $this->preloadAvailableLanguages();

        if (!array_key_exists($language, $this->languages)) {
            $language = Locale::SYSTEM_LOCALE;
        }

        if (!isset($this->cache[$language][$ns])) {
            if ($language === Locale::SYSTEM_LOCALE) {
                $this->cache[$language][$ns] = $this->loadSystemNS($ns);
            } else {
                $this->cache[$language][$ns] = $this->getDriver()->loadNS($language, $ns);
            }
        }

        return $this->cache[$language][$ns];
    }

    protected function getFallbackTranslation(string $ns, string $key, ?string $context = null): string
    {
        $ns .= ':' . $key;

        if ($context !== null && $context !== '') {
            $ns .= '_' . $context;
        }

        return $ns;
    }

    protected function getTranslationText(
        LanguageInfo $language,
        string $ns,
        string $key,
        ?string $context = null,
        int $count = 1
    ): ?string {
        $this->preloadAvailableLanguages();
        $plural_form = $language->plural()->form($count);

        $languages = $language->fallback();
        array_unshift($languages, $language->locale()->id());
        $languages[] = Locale::SYSTEM_LOCALE;
        $languages = array_unique($languages);

        $path = explode('.', $key);
        $key = array_pop($path);

        foreach ($languages as $lang) {
            if (!array_key_exists($lang, $this->languages)) {
                continue;
            }

            $data = $this->loadNS($lang, $ns);
            if (!is_array($data)) {
                continue;
            }

            foreach ($path as $p) {
                if (!isset($data[$p])) {
                    continue 2;
                }
                $data = $data[$p];
            }

            if (!is_array($data)) {
                unset($data);
                continue;
            }

            $text = $this->getTranslationDataWithContext($data, $key, $context, $plural_form);
            if ($text !== null) {
                return $text;
            }
        }

        return null;
    }

    protected function getTranslationDataWithContext(
        array $data,
        string $key,
        ?string $context = null,
        int $plural_form = 0
    ): ?string {
        // Check context
        if ($context !== null && $context !== '') {
            $ret = $this->getTranslationData($data, $key . '_' . $context, $plural_form);
            if ($ret !== null) {
                return $ret;
            }
            unset($ret, $context);
            // Fallback
        }

        // No context
        return $this->getTranslationData($data, $key, $plural_form);
    }

    protected function getTranslationData(array $data, string $key, int $plural_form): ?string
    {
        // Exact plural
        $keys = [$key . '_' . $plural_form];

        if ($plural_form == 0) {
            // Singular
            $keys[] = $key;
            // Fallback to plural
            $keys[] = $key . '_plural';
        } else {
            // Plural
            $keys[] = $key . '_plural';
            // Fallback to singular
            $keys[] = $key;
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return null;
    }

    protected function format(string $text, array $params, LanguageInfo $language): string
    {
        return preg_replace_callback(
            '/\{\s*([a-z0-9_\-\.]+)\s*(?:\|\s*(.+?)\s*)?(?=\})\}/ui',
            $this->getFormatFunction($language, $params),
            $text
        );
    }

    protected function getFormatFunction(LanguageInfo $language, array $params): callable
    {
        return function (array $m) use ($language, &$params) {
            $path = explode('.', $m[1]);
            $value = $params;

            foreach ($path as $p) {
                if (is_array($value)) {
                    if (!array_key_exists($p, $value)) {
                        return $m[0];
                    }
                    $value = $value[$p];
                } elseif (is_object($value)) {
                    if (!property_exists($value, $p)) {
                        return $m[0];
                    }
                    $value = $value->{$p};
                } else {
                    return $m[0];
                }
            }
            unset($path, $p, $params);

            if (!isset($m[2])) {
                // No filters
                return $value;
            }

            $filters = preg_split('~(?<!\\\\)\|~', $m[2]);

            foreach ($filters as $filter) {
                $filter = trim($filter);
                if (!$filter) {
                    continue;
                }
                $filter = str_replace("\\|", "|", $filter);
                $args = preg_split('~(?<!\\\\)\:~', $filter);
                $args = str_replace("\\:", ":", $args);
                $filter = trim(array_shift($args));
                if (!$filter) {
                    continue;
                }
                if ($filter = $this->getFilter($filter)) {
                    $value = $filter($value, $args, $language);
                }
                unset($filter, $args);
            }

            return $value;
        };
    }

    abstract protected function loadSystemNS(string $ns): ?array;

    abstract protected function getFilter(string $name): ?callable;
}