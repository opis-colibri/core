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

use Locale as IntlLocale,
    ResourceBundle as IntlResourceBundle;

class DefaultLocale implements Locale
{

    /** Regex for locale parsing */
    const LOCALE_REGEX = '~^
(?<language>[a-z]{2})
(?:(?:-|_)(?<script>[A-Z][a-z]{3}))?
(?:(?:-|_)(?<region>[A-Z]{2}))?
(?:(?:-|_){1,2}(?<variant>[a-zA-Z0-9]+))?
(?:(?:-|_)(?:x|X)(?:-|_)(?<private>[a-zA-Z0-9]+))?
(?:@(?<options>.*))?
$~x';

    /** @see https://en.wikipedia.org/wiki/Right-to-left */
    const RTL_SCRIPTS = [
        'Arab', 'Aran',
        'Hebr', 'Samr',
        'Syrc', 'Syrn', 'Syrj', 'Syre',
        'Mand',
        'Thaa',
        'Mend',
        'Nkoo',
        'Adlm',
    ];

    protected string $id;

    protected string $language;

    protected ?string $script = null;

    protected ?string $region = null;

    protected bool $rtl = false;

    /**
     * @param string $id Canonical name
     * @param string $language Two letters code
     * @param string|null $script Script name ISO 15924
     * @param string|null $region Two letters code
     * @param bool $rtl
     */
    public function __construct(string $id, string $language,
                                ?string $script = null, ?string $region = null, bool $rtl = false)
    {
        $this->id = $id;
        $this->language = $language;
        $this->script = $script;
        $this->region = $region;
        $this->rtl = $rtl;
    }

    /**
     * @inheritdoc
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function language(): string
    {
        return $this->language;
    }

    /**
     * @inheritdoc
     */
    public function script(): ?string
    {
        return $this->script;
    }

    /**
     * @inheritdoc
     */
    public function region(): ?string
    {
        return $this->region;
    }

    /**
     * @inheritdoc
     */
    public function rtl(): bool
    {
        return $this->rtl;
    }

    /**
     * @param string $locale
     * @return DefaultLocale
     */
    public static function create(string $locale = null): self
    {
        if ($locale === null) {
            $locale = self::SYSTEM_LOCALE;
        }
        $locale = self::canonicalize($locale);
        $p = self::parse($locale);

        return new self(
            $locale,
            $p['language'] ?? 'en',
            $p['script'] ?? null,
            $p['region'] ?? null,
            self::isScriptRTL($p['script'] ?? null)
        );
    }

    /**
     * @param array $locale
     * @return DefaultLocale
     */
    public static function fromArray(array $locale): self
    {
        $name = $locale['id'] ?? $locale['name'] ?? null;
        if (!$name && isset($locale['language'])) {
            $name = $locale['language'];
            unset($locale['language']);
            if (isset($locale['script'])) {
                $name .= '_' . $locale['script'];
                unset($locale['script']);
            }
            if (isset($locale['region'])) {
                $name .= '_' . $locale['region'];
                unset($locale['region']);
            }
        } else {
            $locale = self::SYSTEM_LOCALE;
        }

        $name = self::canonicalize($name);
        $locale = array_filter($locale, static fn ($value) => $value !== null);
        $locale += self::parse($name);

        return new self(
            $name,
            $locale['language'] ?? 'en',
            $locale['script'] ?? null,
            $locale['region'] ?? null,
            $locale['rtl'] ?? self::isScriptRTL($locale['script'] ?? null)
        );
    }

    /**
     * Check if the script is RTL
     * @param string|null $script
     * @return bool
     */
    public static function isScriptRTL(?string $script = null): bool
    {
        if ($script === null || $script === '') {
            return false;
        }

        return in_array($script, self::RTL_SCRIPTS);
    }

    /**
     * @param string $locale
     * @return array
     */
    public static function parse(string $locale): array
    {
        if (IntlChecker::extensionExists()) {
            return IntlLocale::parseLocale($locale) ?: [];
        }
        if (!preg_match(self::LOCALE_REGEX, $locale, $m)) {
            return [];
        }
        return [
            'language' => $m['language'],
            'script' => $m['script'] ?? null,
            'region' => $m['region'] ?? null,
            'variant1' => $m['variant'] ?? null,
            'private1' => $m['private'] ?? null,
            'options' => $m['options'] ?? null
        ];
    }

    /**
     * @param array $tags
     * @return string
     */
    public static function compose(array $tags): string
    {
        if (IntlChecker::extensionExists()) {
            return IntlLocale::composeLocale($tags);
        }

        $list = [];

        if (isset($tags['language']) && $tags['language'] !== '') {
            $list[] = strtolower($tags['language']);
        }
        else {
            $list[] = 'en';
        }

        if (isset($tags['script']) && $tags['script'] !== '') {
            $list[] = ucwords(strtolower($tags['script']));
        }

        if (isset($tags['region']) && $tags['region'] !== '') {
            $list[] = strtoupper($tags['region']);
        }

        if (isset($tags['variant1']) || isset($tags['private1'])) {
            $v = $p = [];

            for ($i = 1; $i < 15; $i++) {
                if (isset($tags['variant' . $i])) {
                    $v[] = strtolower($tags['variant' . $i]);
                }
                if (isset($tags['private' . $i])) {
                    $p[] = strtolower($tags['private' . $i]);
                }
            }

            if ($v) {
                $list = array_merge($list, $v);
            }
            if ($p) {
                $list[] = 'x';
                $list = array_merge($list, $p);
            }
        }

        return implode('_', $list);
    }

    /**
     * @param string $locale
     * @return string
     */
    public static function canonicalize(string $locale): string
    {
        if (IntlChecker::extensionExists()) {
            return IntlLocale::canonicalize($locale);
        }

        if (!preg_match(self::LOCALE_REGEX, $locale,$m)) {
            return $locale;
        }

        $locale = self::compose($m);

        if (isset($m['options']) && $m['options'] !== '') {
            $locale .= '@' . trim($m['options']);
        }

        return $locale;
    }

    /**
     * @return string[]
     */
    public static function systemLocales(): array
    {
        if (!IntlChecker::extensionExists()) {
            return [self::SYSTEM_LOCALE];
        }

        $locales = IntlResourceBundle::getLocales('');
        array_unshift($locales, self::SYSTEM_LOCALE);

        return $locales;
    }

    /**
     * @param string $locale
     * @param string|null $in_language
     * @return string
     */
    public static function getDisplayLanguage(string $locale, ?string $in_language = null): string
    {
        if (IntlChecker::extensionExists()) {
            return IntlLocale::getDisplayLanguage($locale, $in_language ?? self::SYSTEM_LOCALE);
        }

        return self::parse($locale)['language'] ?? $locale;
    }

    /**
     * @param string $locale
     * @param string|null $in_language
     * @return string
     */
    public static function getDisplayName(string $locale, ?string $in_language = null): string
    {
        if (IntlChecker::extensionExists()) {
            return IntlLocale::getDisplayName($locale, $in_language ?? self::SYSTEM_LOCALE);
        }

        return $locale;
    }
}