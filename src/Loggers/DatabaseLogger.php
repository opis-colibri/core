<?php
/* ===========================================================================
 * Copyright 2019-2021 Zindex Software
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

namespace Opis\Colibri\Loggers;

use DateTime;
use Psr\Log\AbstractLogger;
use Opis\Database\{Connection, Database, Schema, Schema\Blueprint};

class DatabaseLogger extends AbstractLogger
{
    protected Database $db;
    protected string $table;
    protected array $columns;

    /**
     * @param Connection $connection
     * @param string $table
     * @param array $columns
     */
    public function __construct(Connection $connection, string $table = 'logs', array $columns = [])
    {
        $this->db = new Database($connection);
        $this->table = $table;
        $this->columns = $columns + [
            'id' => 'id',
            'level' => 'level',
            'message' => 'message',
            'context' => 'context',
            'date' => 'date',
        ];
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array())
    {
        $cols = $this->columns;

        $data = [];

        $data[$cols['level']] = (string) $level;
        $data[$cols['message']] = (string) $message;
        $data[$cols['context']] = $context
            ? json_encode($context, JSON_UNESCAPED_SLASHES)
            : null;
        $data[$cols['date']] = new DateTime();

        $this->db->insert($data)->into($this->table);
    }

    public static function setup(Schema $schema, string $table_name = 'logs', array $columns = []): void
    {
        $columns += [
            'id' => 'id',
            'level' => 'level',
            'message' => 'message',
            'context' => 'context',
            'date' => 'date',
        ];

        $schema->create($table_name, static function (Blueprint $table) use ($columns) {
            $table->integer($columns['id'])->notNull()->unsigned()->size('big')->autoincrement()->primary();
            $table->string($columns['level'], 32)->notNull();
            $table->text($columns['message'])->notNull();
            $table->binary($columns['context'])->size('big')->defaultValue(null);
            $table->dateTime($columns['date'])->notNull();

            $table->index($columns['level']);
        });
    }
}
