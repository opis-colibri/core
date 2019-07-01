<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

namespace Opis\Colibri\Validation;

use Opis\Colibri\Validation\Rules\Csrf;
use Opis\Validation\IValidationRule;
use Opis\Validation\RuleCollection as BaseCollection;
use function Opis\Colibri\Functions\{
    app
};

class RuleCollection extends BaseCollection
{
    /**
     * @inheritDoc
     */
    protected function resolveRule(string $name): ?IValidationRule
    {
        if (null !== $rule = app()->getCollector()->getValidators()->get($name)) {
            return $rule;
        }

        switch ($name) {
            case 'field:csrf':
                return new Csrf();
        }

        return parent::resolveRule($name);
    }
}