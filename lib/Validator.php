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

namespace Opis\Colibri;

use Opis\Utils\Validator as BaseValidator;

class Validator extends BaseValidator
{
    /** @var    \Opis\Colibri\Application */
    protected $app;

    /**
     * Constructor
     * 
     * @param   \Opis\Colibri\Application   $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct($app->collect('Validators')->getList());
    }

    /**
     * Push a validator
     * 
     * @param   array   $item
     * 
     * @return  $this
     */
    protected function push(array $item)
    {
        $item['error']['text'] = $this->app->t($item['error']['text']);
        return parent::push($item);
    }

    /**
     * Validate CSRF values
     * 
     * @param   string  $error  (optional)
     * 
     * @return  $this
     */
    public function csrf($error = 'Invalid CSRF token')
    {
        return $this->push(array(
                'validator' => array(
                    'callback' => array($this, 'validate' . ucfirst(__FUNCTION__)),
                    'arguments' => array(),
                ),
                'error' => array(
                    'text' => $error,
                    'variables' => array(),
                ),
        ));
    }

    /**
     * Validator's callback
     * 
     * @param   string  $token
     * @return  boolean
     */
    protected function validateCsrf($token)
    {
        return $this->app->csrfValidate($token);
    }
}
