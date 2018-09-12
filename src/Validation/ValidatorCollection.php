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

namespace Opis\Colibri\Validation;

use Opis\Colibri\Validation\Validators\Csrf;
use Opis\Validation\ValidatorCollection as BaseCollection;
use Opis\Validation\ValidatorInterface;
use Opis\Validation\Validators\Between;
use Opis\Validation\Validators\Email;
use Opis\Validation\Validators\Equal;
use Opis\Validation\Validators\FileMatch;
use Opis\Validation\Validators\FileType;
use Opis\Validation\Validators\GreaterThan;
use Opis\Validation\Validators\GreaterThanOrEqual;
use Opis\Validation\Validators\Length;
use Opis\Validation\Validators\LessThan;
use Opis\Validation\Validators\LessThanOrEqual;
use Opis\Validation\Validators\Match;
use Opis\Validation\Validators\MaxLength;
use Opis\Validation\Validators\MinLength;
use Opis\Validation\Validators\Number;
use Opis\Validation\Validators\Regex;
use Opis\Validation\Validators\Required;
use Opis\Validation\Validators\RequiredFile;
use function Opis\Colibri\Functions\{
    app
};

class ValidatorCollection extends BaseCollection
{
    protected $classList;

    public function __construct()
    {
        parent::__construct();

        $this->classList = $list = app()->getCollector()->getValidators();

        $classes = [
            'between' => Between::class,
            'csrf' => Csrf::class,
            'email' => Email::class,
            'equal' => Equal::class,
            'fileMatch' => FileMatch::class,
            'fileType' => FileType::class,
            'gt' => GreaterThan::class,
            'gte' => GreaterThanOrEqual::class,
            'length' => Length::class,
            'lt' => LessThan::class,
            'lte' => LessThanOrEqual::class,
            'match' => Match::class,
            'maxLength' => MaxLength::class,
            'minLength' => MinLength::class,
            'number' => Number::class,
            'regex' => Regex::class,
            'required' => Required::class,
            'requiredFile' => RequiredFile::class,
        ];

        $types = $list->getNames();

        $classes = array_diff_key($classes, array_flip($types));

        foreach ($classes as $name => $class) {
            $list->add($name, $class);
        }
    }


    /**
     * @param string $name
     * @return null|ValidatorInterface
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->validators)) {
            $this->validators[$name] = $this->classList->get($name);
        }

        return $this->validators[$name];
    }
}