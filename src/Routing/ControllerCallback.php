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

namespace Opis\Colibri\Routing;

final class ControllerCallback
{
    private string $method;
    private string $className;
    private bool $isStatic;

    /**
     * @var self[]
     */
    private static array $instances = [];

    /**
     * Constructor
     *
     * @param   string $class
     * @param   string $method
     * @param   boolean $static (optional)
     */
    private function __construct(string $class, string $method, bool $static = false)
    {
        $this->className = $class;
        $this->method = $method;
        $this->isStatic = $static;
    }

    /**
     * Make the instances of this class being a valid callable value
     */
    public function __invoke()
    {
        // nop
    }

    /**
     * Returns the class name
     *
     * @return  string
     */
    public function getClass(): string
    {
        return $this->className;
    }

    /**
     * Returns the param's name that references the method
     *
     * @return  string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Indicates if the referenced method is static or not
     *
     * @return  boolean
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    public static function get(string $class, string $method, bool $static = false): self
    {
        $key = trim($class) . ($static ? '::' : '->') . trim($method);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($class, $method, $static);
        }

        return self::$instances[$key];
    }
}