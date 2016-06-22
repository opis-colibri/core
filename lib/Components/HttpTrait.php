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

namespace Opis\Colibri\Components;


use Opis\Http\Request;
use Opis\Http\Response;
use Opis\HttpRouting\HttpError;

trait HttpTrait
{
    use ApplicationTrait;

    /**
     * @return Request
     */
    protected function request(): Request
    {
        return $this->getApp()->getHttpRequest();
    }

    /**
     * @return Response
     */
    protected function response(): Response
    {
        return $this->getApp()->getHttpResponse();
    }

    /**
     * @param string $location
     * @param int $code
     * @param array $query
     */
    protected function redirect(string $location, int $code = 302, array $query = array())
    {
        if (!empty($query)) {
            foreach ($query as $key => $value) {
                $query[$key] = $key . '=' . urlencode($value);
            }
            $location = rtrim($location) . '?' . implode('&', $query);
        }

        $this->response()->redirect($location, $code);
    }

    /**
     * @return HttpError
     */
    protected function pageNotFound(): HttpError
    {
        $this->httpError(404);
    }

    /**
     * @return HttpError
     */
    protected function accessDenied(): HttpError
    {
        return $this->httpError(403);
    }

    /**
     * @param int $code
     * @return HttpError
     */
    protected function httpError(int $code): HttpError
    {
        return new HttpError($code);
    }
}