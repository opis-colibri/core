<?php
/* ===========================================================================
 * Copyright 2020 Zindex Software
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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Serializable\JsonSchemaResolvers;
use Opis\JsonSchema\Resolvers\{
    ContentEncodingResolver,
    ContentMediaTypeResolver,
    FilterResolver,
    FormatResolver,
    SchemaResolver
};

/**
 * @property JsonSchemaResolvers $data
 */
class JsonSchemaResolversCollector extends BaseCollector
{
    public function __construct()
    {
        parent::__construct(new JsonSchemaResolvers());
    }

    public function filters(): FilterResolver
    {
        return $this->data->filters();
    }

    public function formats(): FormatResolver
    {
        return $this->data->formats();
    }

    public function contentEncodings(): ContentEncodingResolver
    {
        return $this->data->contentEncodings();
    }

    public function contentMediaTypes(): ContentMediaTypeResolver
    {
        return $this->data->contentMediaTypes();
    }

    public function schema(): SchemaResolver
    {
        return $this->data->schema();
    }

    public function setParserOption(string $name, $value): self
    {
        $this->data->setParserOption($name, $value);
        return $this;
    }

    public function addParserFactory(string $name, callable $factory): self
    {
        $this->data->addParserFactory($name, $factory);
        return $this;
    }

    public function addLoader(string $name, ?string $dir = null, ?callable $dynamic = null): self
    {
        $this->data->addLoader($name, $dir, $dynamic);
        return $this;
    }

    public function setMaxErrors(int $max): self
    {
        $this->data->setMaxErrors($max);
        return $this;
    }
}