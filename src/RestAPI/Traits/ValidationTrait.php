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

use stdClass;
use Opis\JsonSchema\{Errors\ValidationError, JsonPointer, Schema, Uri};
use function Opis\Colibri\validator;

// TODO: Review this
trait ValidationTrait
{
    /**
     * @param mixed $data
     * @param stdClass|boolean|string|Uri|Schema $schema
     * @param array|null $globals
     * @param array|null $slots
     * @return ValidationError|null
     */
    protected function validate($data, $schema, ?array $globals = null, ?array $slots = null): ?ValidationError
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

    protected function formatErrorMessages(ValidationError $error, bool $multiple = true,
        ?callable $formatter = null, ?callable $key_formatter = null): array
    {
        if (!$key_formatter) {
            $key_formatter = JsonPointer::class . '::pathToString';
        }

        if (!$formatter) {
            $formatter = [$this, 'formatMessage'];
        }

        $list = [];

        /**
         * @var ValidationError $error
         * @var string $message
         */

        if ($multiple) {
            foreach ($this->getErrorMessages($error) as $error => $message) {
                $key = $key_formatter($error->data()->fullPath());

                if (!isset($list[$key])) {
                    $list[$key] = [];
                }

                $list[$key][] = $formatter ? $formatter($message, $error) : $message;
            }
        } else {
            foreach ($this->getErrorMessages($error) as $error => $message) {
                $key = $key_formatter($error->data()->fullPath());
                if (!isset($list[$key])) {
                    $list[$key] = $formatter ? $formatter($message, $error) : $message;
                }
            }
        }

        return $list;
    }

    private function formatMessage(string $message, ValidationError $error): string
    {
        $args = $error->args();

        if (!$args) {
            return $message;
        }

        return preg_replace_callback('~\@([a-z0-9\-_.:]+)~imu', static function (array $m) use ($args) {
            return $args[$m[1]] ?? $m[0];
        }, $message);
    }

    private function getErrorMessages(ValidationError $error): iterable
    {
        $data = $error->schema()->info()->data();

        $map = null;
        $pMap = null;

        if (is_object($data) && isset($data->{'$error'})) {
            $map = $data->{'$error'};

            if (is_string($map)) {
                // We have an global error
                yield $error => $map;
                return;
            }

            if (is_object($map)) {
                if (isset($map->{$error->keyword()})) {
                    $pMap = $map->{'*'} ?? null;
                    $map = $map->{$error->keyword()};
                    if (is_string($map)) {
                        yield $error => $map;
                        return;
                    }
                } elseif (isset($map->{'*'})) {
                    yield $error => $map->{'*'};
                    return;
                }
            }
        }

        if (!is_object($map)) {
            $map = null;
        }

        $subErrors = $error->subErrors();

        if (!$subErrors) {
            yield $error => $pMap ?? $error->message();
            return;
        }

        if ($map) {
            foreach ($subErrors as $subError) {
                $path = $subError->data()->path();
                if (count($path) !== 1) {
                    yield from $this->getErrorMessages($subError);
                } else {
                    $path = $path[0];
                    if (isset($map->{$path})) {
                        yield $subError => $map->{$path};
                    } elseif (isset($map->{'*'})) {
                        yield $subError => $map->{'*'};
                    } else {
                        yield from $this->getErrorMessages($subError);
                    }
                }
            }
        } else {
            foreach ($subErrors as $subError) {
                yield from $this->getErrorMessages($subError);
            }
        }
    }
}