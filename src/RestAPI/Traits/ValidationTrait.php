<?php
/* ============================================================================
 * Copyright 2019-2020 Zindex Software
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

namespace Opis\Colibri\RestAPI\Traits;

use stdClass, Throwable;
use Opis\JsonSchema\{Schema, Uri};
use Opis\JsonSchema\Errors\{ErrorFormatter, ValidationError};
use function Opis\Colibri\{env, logger, validator};

trait ValidationTrait
{
    protected ?ErrorFormatter $errorFormatter = null;

    protected function errorFormatter(): ErrorFormatter
    {
        if ($this->errorFormatter === null) {
            $this->errorFormatter = new ErrorFormatter();
        }
        return $this->errorFormatter;
    }

    /**
     * @param mixed $data
     * @param stdClass|boolean|string|Uri|Schema $schema
     * @param array|null $globals
     * @param array|null $slots
     * @return ValidationError|null
     */
    protected function validate(mixed $data, object|bool|string $schema, ?array $globals = null, ?array $slots = null): ?ValidationError
    {
        $validator = validator();

        if (is_string($schema) || ($schema instanceof Uri)) {
            return $validator->uriValidation($data, $schema, $globals, $slots);
        }

        if ($schema instanceof Schema) {
            return $validator->schemaValidation($data, $schema, $globals, $slots);
        }

        return $validator->dataValidation($data, $schema, $globals, $slots);
    }

    /**
     * @param ValidationError $error
     * @param bool $multiple
     * @param callable|null $formatter
     * @param callable|null $key_formatter
     * @return array
     */
    protected function formatErrors(
        ValidationError $error,
        bool $multiple = false,
        ?callable $formatter = null,
        ?callable $key_formatter = null
    ): array {
        return $this->errorFormatter()->format($error, $multiple, $formatter, $key_formatter);
    }
}