<?php
/* ============================================================================
 * Copyright 2019 Zindex Software
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

use stdClass;
use Opis\JsonSchema\{Errors\ValidationError, Exceptions\SchemaException};
use function Opis\Colibri\validator;

// TODO: Review this
trait ValidationTrait
{
    /**
     * @param $data
     * @param stdClass|boolean|string $schema
     * @param array $globals
     * @param boolean $safe
     * @return ValidationError|null
     */
    protected function validate($data, $schema, array $globals = [], bool $safe = true): ?ValidationError
    {
        $validator = validator();

        try {
            if (is_string($schema)) {
                $result = $validator->uriValidation($data, $schema, $globals);
            } else {
                $result = $validator->dataValidation($data, $schema, $globals);
            }
        } catch (SchemaException $e) {
            throw $e; // just throw it back for now and ignore $safe
        }

        return $result;
    }
}