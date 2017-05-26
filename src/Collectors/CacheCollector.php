<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

use Opis\Cache\CacheInterface;
use Opis\Colibri\CollectingContainer;
use Opis\Colibri\Serializable\StorageCollection;

/**
 * Class CacheCollector
 *
 * @package Opis\Colibri\Collectors
 *
 * @method  StorageCollection    data()
 * @property StorageCollection $dataObject
 */
class CacheCollector extends CollectingContainer
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new StorageCollection(self::class . '::factory'));
    }

    /**
     * @param $storage
     * @param callable $constructor
     * @return CacheCollector
     */
    public function register($storage, callable $constructor): self
    {
        $this->dataObject->add($storage, $constructor);
        return $this;
    }

    /**
     * @param string $storage
     * @param callable $factory
     * @return CacheInterface
     */
    public static function factory(string $storage, callable $factory): CacheInterface
    {
        return $factory($storage);
    }
}
