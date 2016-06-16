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

namespace Opis\Colibri\Components;

use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Database\Schema;

trait DatabaseTrait
{
    use ApplicationTrait;

    /**
     * @param string|null $name
     * @return Connection
     */
    public function connection(string $name = null): Connection
    {
        return $this->getApp()->connection($name);
    }

    /**
     * @param string|null $connection
     * @return Database
     */
    public function db(string $connection = null): Database
    {
        return $this->getApp()->database($connection);
    }

    /**
     * @param string|null $connection
     * @return Schema
     */
    public function schema(string $connection = null): Schema
    {
        return $this->getApp()->schema($connection);
    }
}