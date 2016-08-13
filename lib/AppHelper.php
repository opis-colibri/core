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

use Opis\Colibri\Components\CacheTrait;
use Opis\Colibri\Components\ConfigTrait;
use Opis\Colibri\Components\ContractTrait;
use Opis\Colibri\Components\CSRFTrait;
use Opis\Colibri\Components\DatabaseTrait;
use Opis\Colibri\Components\EventTrait;
use Opis\Colibri\Components\HttpTrait;
use Opis\Colibri\Components\InfoTrait;
use Opis\Colibri\Components\LogTrait;
use Opis\Colibri\Components\SessionTrait;
use Opis\Colibri\Components\UtilsTrait;
use Opis\Colibri\Components\ViewTrait;

class AppHelper
{
    use CacheTrait {
        cache as public;
    }
    use ConfigTrait {
        config as public;
    }
    use ContractTrait {
        make as public;
    }
    use CSRFTrait {
        generateCSRFToken as public;
        validateCSRFToken as public;
    }
    use DatabaseTrait {
        db as public;
        schema as public;
        connection as public;
        orm as public;
    }
    use EventTrait {
        emit as public;
        dispatch as public;
    }
    use HttpTrait {
        request as public;
        response as public;
        redirect as public;
        pageNotFound as public;
        accessDenied as public;
    }
    use InfoTrait {
        assetsDir as public;
        assetsPath as public;
        publicDir as public;
        rootDir as public;
        writableDir as public;
        vendorDir as public;
        composerFile as public;
        bootstrapFile as public;
        installMode as public;
        cliMode as public;
    }
    use LogTrait {
        log as public;
    }
    use SessionTrait {
        session as public;
    }
    use UtilsTrait {
        v as public;
        r as public;
        t as public;
        getAsset as public;
        getURL as public;
        controller as public;
        module as public;
    }
    use ViewTrait {
        view as public;
        render as public;
    }

    /** @var  Application */
    protected $app;

    /**
     * App constructor.
     * @param Application $app
     */
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