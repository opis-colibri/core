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

namespace Opis\Colibri\Utils;

final class Version
{
    public static function eval(string $version1, string $version2, string $sign = '='): bool
    {
        $result = self::compare($version1, $version2);

        return match($sign) {
            '=', '==' => $result === 0,
            '!=', '<>' => $result !== 0,
            '<' => $result < 0,
            '<=' => $result <= 0,
            '>' => $result > 0,
            '>=' => $result >= 0,
            default => false,
        };
    }

    public static function match(string $version1, string $version2, int|string $replace = '*'): bool
    {
        return 0 === self::compare($version1, $version2, $replace);
    }

    public static function normalize(string $version): string
    {
        $version = preg_replace('/[_\-+]+/', '.', $version);
        $version = preg_replace('/\#+/', '#', $version);
        $version = preg_replace('/([0-9]+)([a-zA-Z]+)/', '$1.$2', $version);
        $version = preg_replace('/([a-zA-Z]+)([0-9]+)/', '$1.$2', $version);
        $version = preg_replace('/(\#+)([^\#.]+)/', '$1.$2', $version);
        $version = preg_replace('/([^\#.]+)(\#+)/', '$1.$2', $version);
        return strtolower(trim($version, '.'));
    }

    public static function compare(string $version1, string $version2, int|string|null $replace = null): int
    {
        $version1 = explode('.', self::normalize($version1));
        $version2 = explode('.', self::normalize($version2));

        $c1 = count($version1);
        $c2 = count($version2);
        $max = max($c1, $c2);

        if ($c1 !== $c2) {
            if ($c1 > $c2) {
                $v = &$version2;
            } else {
                $v = &$version1;
                $max = $c2;
            }

            $replace ??= 0;
            for ($i = 0, $l = abs($c1 - $c2); $i < $l; $i++) {
                $v[] = $replace;
            }
        }

        $version1 = array_map(self::class . '::map', $version1);
        $version2 = array_map(self::class . '::map', $version2);

        for ($i = 0; $i < $max; $i++) {
            $v1 = $version1[$i];
            $v2 = $version2[$i];

            if ($v1 === '*' || $v2 === '*') {
                continue;
            }

            $c1 = is_int($v1);
            $c2 = is_int($v2);

            if ($c1 && $c2) {
                if ($v1 == $v2) {
                    continue;
                }

                return $v1 < $v2 ? -1 : 1;
            } elseif ($c1 == $c2) {
                $r = strcmp($v1, $v2);

                if ($r == 0) {
                    continue;
                }

                return $r < 0 ? -1 : 1;
            } else {
                return $c1 === false ? -1 : 1;
            }
        }

        return 0;
    }

    private const MAP = [
        'dev' => -6,
        'alpha' => -5,
        'a' => -5,
        'beta' => -4,
        'b' => -4,
        'rc' => -3,
        '#' => -2,
        'pl' => -1,
        'p' => -1,
    ];

    private static function map(string $value): int|string
    {
        return self::MAP[$value] ?? (is_numeric($value) ? (int)$value : $value);
    }
}