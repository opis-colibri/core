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

use Opis\Colibri\Application;
use Opis\Colibri\Components\CSRFTrait;
use Opis\Colibri\Components\UtilsTrait;
use Opis\Colibri\Components\ViewTrait;

class ViewHelper
{
    use ViewTrait{
        view as public;
        render as public;
    }
    use UtilsTrait{
        getAsset as public;
        getURL as public;
        r as public;
        v as public;
        t as public;
    }
    use CSRFTrait{
        generateCSRFToken as public csrfToken;
    }

    /** @var  Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }

}