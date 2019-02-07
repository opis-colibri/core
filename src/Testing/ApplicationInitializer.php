<?php
/* ============================================================================
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

namespace Opis\Colibri\Testing;

use Opis\Colibri\Core\{
    IApplicationInitializer, IApplicationContainer
};
use Opis\Colibri\Testing\Builders\AppInitBuilder;

class ApplicationInitializer implements IApplicationInitializer
{
    /** @var AppInitBuilder */
    protected $builder;

    /**
     * @param AppInitBuilder $builder
     */
    public function __construct(AppInitBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return AppInitBuilder
     */
    public function builder(): AppInitBuilder
    {
        return $this->builder;
    }

    /**
     * @inheritDoc
     */
    public function init(IApplicationContainer $app)
    {
        $builder = $this->builder;

        date_default_timezone_set($builder->getTimezone());

        $app->setConfigDriver($builder->getConfigDriver());
        $app->setCacheDriver($builder->getCacheDriver());
        $app->setSessionHandler($builder->getSessionHandler());
        $app->setDefaultLanguage($builder->getLanguage());
        $app->setTranslatorDriver($builder->getTranslator());
        $app->setDefaultLogger($builder->getLogger());
        $app->setDatabaseConnection($builder->getDatabaseConnection());
    }
}