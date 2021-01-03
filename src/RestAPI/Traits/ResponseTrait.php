<?php
/* ============================================================================
 * Copyright 2020 Zindex Software
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

namespace Opis\Colibri\RestAPI\Traits;

use Opis\Colibri\Http\Responses\JSONResponse;

trait ResponseTrait
{
    /**
     * @param int $status
     * @param mixed $content
     * @param array|null $headers
     * @return JSONResponse
     */
    public function response(int $status, $content = null, ?array $headers = null): JSONResponse
    {
        return new JSONResponse($content, $status, $headers ?? []);
    }

    /**
     * 200 OK
     * @param $data
     * @return JSONResponse
     */
    public function http200($data): JSONResponse
    {
        return $this->response(200, $data);
    }

    /**
     * 201 Created
     * @param string $id
     * @param string|null $location
     * @return JSONResponse
     */
    public function http201(string $id, string $location = null): JSONResponse
    {
        if ($location !== null) {
            $location = ['Location' => $location];
        }
        return $this->response(201, ['id' => $id], $location);
    }

    /**
     * 204 No content
     * @return JSONResponse
     */
    public function http204(): JSONResponse
    {
        return $this->response(204);
    }

    /**
     * 403 Forbidden
     * @param mixed $body
     * @return JSONResponse
     */
    public function http403($body = null): JSONResponse
    {
        return $this->response(403, $body);
    }

    /**
     * 404 Not found
     * @param mixed $body
     * @return JSONResponse
     */
    public function http404($body = null): JSONResponse
    {
        return $this->response(404, $body);
    }

    /**
     * 405 Method not allowed
     * @param mixed $body
     * @return JSONResponse
     */
    public function http405($body = null): JSONResponse
    {
        return $this->response(405, $body);
    }

    /**
     * 422 Unprocessable entity
     * @param array $errors
     * @return JSONResponse
     */
    public function http422(array $errors = []): JSONResponse
    {
        return $this->response(422, ['errors' => $errors]);
    }

    /**
     * 500 Internal server error
     * @param mixed $error
     * @return JSONResponse
     */
    public function http500($error = null): JSONResponse
    {
        return $this->response(500, $error);
    }
}