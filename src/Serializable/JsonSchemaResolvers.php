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

namespace Opis\Colibri\Serializable;

use Opis\JsonSchema\Resolvers\{
    ContentEncodingResolver,
    ContentMediaTypeResolver,
    FilterResolver,
    FormatResolver,
    SchemaResolver
};
use Opis\JsonSchema\Parsers\{DefaultVocabulary, KeywordParser, PragmaParser, Vocabulary, SchemaParser};
use Opis\JsonSchema\{KeywordValidator, SchemaLoader, Uri, Validator};

class JsonSchemaResolvers
{
    private ?SchemaResolver $schema = null;
    private array $options = [
        'allowDataKeyword' => true,
        'allowKeywordsAlongsideRef' => true,
    ];
    private array $parsers = [];
    private array $resolvers = [];
    private array $loaders = [];
    private int $maxErrors = 1;

    public function filters(): FilterResolver
    {
        if (!isset($this->resolvers['$filters'])) {
            $this->resolvers['$filters'] = new FilterResolver();
        }
        return $this->resolvers['$filters'];
    }

    public function formats(): FormatResolver
    {
        if (!isset($this->resolvers['format'])) {
            $this->resolvers['format'] = new FormatResolver();
        }
        return $this->resolvers['format'];
    }

    public function contentEncodings(): ContentEncodingResolver
    {
        if (!isset($this->resolvers['contentEncoding'])) {
            $this->resolvers['contentEncoding'] = new ContentEncodingResolver();
        }
        return $this->resolvers['contentEncoding'];
    }

    public function contentMediaTypes(): ContentMediaTypeResolver
    {
        if (!isset($this->resolvers['contentMediaType'])) {
            $this->resolvers['contentMediaType'] = new ContentMediaTypeResolver();
        }
        return $this->resolvers['contentMediaType'];
    }

    public function schema(): SchemaResolver
    {
        if ($this->schema === null) {
            $this->schema = new SchemaResolver();
            $this->schema->registerProtocol('json-schema', [$this, 'load']);
        }
        return $this->schema;
    }

    public function setParserOption(string $name, $value): self
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function addParserFactory(string $name, callable $factory): self
    {
        $this->parsers[$name] = $factory;
        return $this;
    }

    public function setMaxErrors(int $max): self
    {
        $this->maxErrors = $max;
        return $this;
    }

    public function addLoader(string $name, ?string $dir = null, ?callable $dynamic = null): self
    {
        if ($dir === null && $dynamic === null) {
            if (isset($this->loaders[$name])) {
                unset($this->loaders[$name]);
            }
        } else {
            $this->loaders[$name] = [$dir, $dynamic];
        }

        return $this;
    }

    public function buildValidator(): Validator
    {
        return new Validator($this->getSchemaLoader(), $this->maxErrors);
    }

    public function __serialize(): array
    {
        return [
            'schema' => $this->schema,
            'loaders' => $this->loaders,
            'resolvers' => $this->resolvers,
            'options' => $this->options,
            'parsers' => $this->parsers,
            'maxErrors' => $this->maxErrors,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->schema = $data['schema'];
        $this->loaders = $data['loaders'];
        $this->resolvers = $data['resolvers'];
        $this->options = $data['options'];
        $this->parsers = $data['parsers'];
        $this->maxErrors = $data['maxErrors'];
    }

    private function getSchemaLoader(): SchemaLoader
    {
        $parser = new SchemaParser($this->resolvers, $this->options, $this->getVocabulary());

        return new SchemaLoader($parser, $this->schema(), true);
    }

    private function getVocabulary(): ?Vocabulary
    {
        $keywords = [];
        $keywordValidators = [];
        $pragmas = [];

        foreach ($this->parsers as $name => $func) {
            if (!is_callable($func)) {
                continue;
            }

            $func = $func($name);
            if (!is_object($func)) {
                continue;
            }

            if ($func instanceof KeywordParser) {
                $keywords[] = $func;
            } elseif ($func instanceof KeywordValidator) {
                $keywordValidators[] = $func;
            } elseif ($func instanceof PragmaParser) {
                $pragmas[] = $func;
            }
        }

        if ($keywords || $keywordValidators || $pragmas) {
            return new DefaultVocabulary($keywords, $keywordValidators, $pragmas);
        }

        return null;
    }

    public function load(Uri $uri)
    {
        $loader = $this->loaders[$uri->host()] ?? null;

        if (!$loader) {
            // No loader registered
            return null;
        }

        if ($loader[1] && $uri->query() !== null && $uri->query() !== '') {
            // If the uri has query-string consider it dynamic
            return $loader[1]($uri);
        }

        if (!$loader[0]) {
            // We don't have a dir
            return null;
        }

        $path = $uri->path();
        if ($path === null || $path === '' || $path === '/') {
            return null;
        }

        if ($path[0] !== '/') {
            $path = './' . $path;
        } else {
            $path = '.' . $path;
        }

        $file = (string) Uri::merge($path, rtrim($loader[0], '/') . '/', false);

        if ($file === '' || !is_file($file)) {
            return null;
        }

        return json_decode(file_get_contents($file), false);
    }
}