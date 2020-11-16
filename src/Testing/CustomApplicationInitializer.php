<?php
/* ============================================================================
 * Copyright 2018-2020 Zindex Software
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

use Opis\Colibri\{
    ApplicationInitializer, Application
};
use Opis\Colibri\Testing\Builders\ApplicationInitializerBuilder;

class CustomApplicationInitializer implements ApplicationInitializer
{

    protected ApplicationInitializerBuilder $builder;

    /**
     * @param ApplicationInitializerBuilder $builder
     */
    public function __construct(ApplicationInitializerBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return ApplicationInitializerBuilder
     */
    public function builder(): ApplicationInitializerBuilder
    {
        return $this->builder;
    }

    /**
     * @inheritDoc
     */
    public function init(Application $app): void
    {
        $builder = $this->builder;

        date_default_timezone_set($builder->getTimezone());

        $app->setConfigDriver($builder->getConfigDriver());
        $app->setCacheDriver($builder->getCacheDriver());
        $app->setSessionHandler($builder->getSessionHandler(), $builder->getSessionConfig());
        $app->setDefaultLanguage($builder->getLanguage());
        $app->setTranslatorDriver($builder->getTranslator());
        $app->setDefaultLogger($builder->getLogger());
        $app->setDatabaseConnection($builder->getDatabaseConnection());
    }
}