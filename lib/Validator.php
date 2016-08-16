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

namespace Opis\Colibri;

use Opis\Validation\DefaultValidatorTrait;
use Opis\Validation\Validator as BaseValidator;
use function Opis\Colibri\Helpers\{t};

class Validator extends BaseValidator
{
    use DefaultValidatorTrait;

    /**
     * @return array
     */
    public function getErrors(): array
    {
        $errors = array();

        foreach (parent::getErrors() as $key => $value) {
            $errors[$key] = t($value);
        }

        return $errors;
    }

    /**
     * @return Validator
     */
    public function csrf(): self
    {
        return $this->push([
            'name' => __FUNCTION__,
            'arguments' => [],
        ]);
    }

    /**
     * @param array $validator
     * @return Validator
     */
    protected function push(array $validator): self
    {
        $this->stack[] = $validator;
        return $this;
    }

}
