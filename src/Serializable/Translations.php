<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

use Serializable;

class Translations implements Serializable
{

    /** @var array */
    protected $data = [];

    /** @var array */
    protected $comments = [];

    /**
     * @param string $ns
     * @param array $data
     * @return Translations
     */
    public function addTranslations(string $ns, array $data): self
    {
        $this->data[$ns] = $data;
        return $this;
    }

    /**
     * @param string $ns
     * @return array
     */
    public function getTranslations(string $ns): array
    {
        return $this->data[$ns] ?? [];
    }

    /**
     * @param string $ns
     * @param string $key
     * @param string|null $comment
     * @param string|null $translators_comment
     * @return Translations
     */
    public function addComment(
        string $ns,
        string $key,
        string $comment = null,
        string $translators_comment = null
    ): self {
        $this->comments[$ns][$key] = [
            'comment' => $comment,
            'translators' => $translators_comment,
        ];
        return $this;
    }

    /**
     * @param string $ns
     * @param string $key
     * @return array|null
     */
    public function getComment(string $ns, string $key)
    {
        return $this->comments[$ns][$key] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize([
            'data' => $this->data,
            'comments' => $this->comments,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($data)
    {
        $un = unserialize($data);
        $this->data = $un['data'] ?? [];
        $this->comments = $un['comments'] ?? [];
    }
}
