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

namespace Opis\Colibri\Session;

final class SessionData
{
    private string $id;
    private int $expire;
    private array $data;
    private int $createdAt;
    private int $updatedAt;

    public function __construct(string $id, int $expire, array $data = [], ?int $createdAt = null, ?int $updatedAt = null)
    {
        if ($createdAt === null) {
            $updatedAt = $createdAt = time();
        }

        if ($updatedAt === null || $createdAt > $updatedAt) {
            $updatedAt = $createdAt;
        }

        $this->id = $id;
        $this->expire = $expire;
        $this->data = $data;

        /** @var int $createdAt */
        $this->createdAt = $createdAt;
        /** @var int $updatedAt */
        $this->updatedAt = $updatedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function expiresAt(): int
    {
        return $this->expire;
    }

    public function setExpirationDate(int $timestamp): void
    {
        $this->expire = $timestamp;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function setData(array $array): self
    {
        $this->data = $array;
        return $this;
    }

    public function createdAt(): int
    {
        return $this->createdAt;
    }

    public function updatedAt(): int
    {
        return $this->updatedAt;
    }

    public function isExpiredAt(int $timestamp): bool
    {
        if ($this->expire === 0) {
            return $this->updatedAt < $timestamp;
        }

        return $this->expire < $timestamp;
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'expire' => $this->expire,
            'createdAt' => $this->createdAt,
            'updatedAt' => time(),
            'data' => $this->data,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->expire = $data['expire'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->data = $data['data'];
    }
}