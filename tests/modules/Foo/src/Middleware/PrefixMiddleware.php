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

namespace Test\Foo\Middleware;

use Opis\Colibri\Routing\Middleware;
use Opis\Colibri\Http\Response;
use Opis\Colibri\Stream\PHPMemoryStream;

class PrefixMiddleware extends Middleware
{
    public function __invoke()
    {
        return $this->next()->modify(static function(Response $response) {
            $body = new PHPMemoryStream('prefix-' . $response->getBody());
            $response->setBody($body);
        });
    }
}