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

interface DateTimeFormatter
{
    /**
     * @param mixed $value
     * @param mixed $date_format
     * @param mixed $time_format
     * @param mixed $timezone
     * @return string|null
     */
    public function format($value = null, $date_format = null, $time_format = null, $timezone = null): ?string;

    /**
     * @param mixed $value
     * @param string $pattern
     * @param mixed $timezone
     * @return string
     */
    public function formatPattern($value, string $pattern, $timezone = null): ?string;

    /**
     * @param mixed $value
     * @param mixed $format
     * @param mixed $timezone
     * @return string
     */
    public function formatDate($value = null, $format = null, $timezone = null): ?string;

    /**
     * @param mixed $value
     * @param mixed $format
     * @param mixed $timezone
     * @return string
     */
    public function formatTime($value = null, $format = null, $timezone = null): ?string;
}