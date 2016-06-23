<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri\Validators;

use Opis\Colibri\Application;
use Opis\Colibri\Components\ContractTrait;
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

class ValidatorCollection extends  BaseCollection
{
    use ContractTrait;

    /** @var  Application */
    protected  $app;

    /** @var  array */
    protected $classes;

    /**
     * ValidatorCollection constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $classes = $app->getCollector()->getValidators();

        $classes += array(
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
        );

        $this->classes = $classes;
        $this->validators = array();
    }

    /**
     * @return Application
     */
    protected function getApp(): Application
    {
        return $this->app;
    }


    /**
     * @param $name
     * @return bool|ValidatorInterface
     */
    public function get($name)
    {
        if(!isset($this->validators[$name])){
            if (isset($this->classes[$name])){
                $validator = $this->make($this->classes[$name]);
                if (!($validator instanceof ValidatorInterface)) {
                    return false;
                }
                return $this->validators[$name] = $validator;
            }
        }

        return isset($this->validators[$name]) ? $this->validators[$name] : false;
    }

}