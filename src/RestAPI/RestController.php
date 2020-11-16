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

namespace Opis\Colibri\RestAPI;

use Opis\Routing\Controller;
use Opis\Http\Responses\JSONResponse;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\Colibri\RestAPI\Traits\{ResponseTrait, ValidationTrait};

abstract class RestController extends Controller
{
    use ResponseTrait, ValidationTrait;

    /**
     * @param ValidationError $error
     * @return JSONResponse
     */
    protected function httpValidationError(ValidationError $error): JSONResponse
    {
        return $this->http422($this->formatErrors($error));
    }

    /**
     * Format of the actions array:
     *
     * [
     *      "action-name-1": [
     *          "get" => "methodNameForGET",
     *          "post" => "methodNameForPOST",
     *      ],
     *      "action-name-2": [
     *          "put" => "methodNameForPUT",
     *          "delete" => "methodNameForDELETE",
     *      ],
     * ]
     *
     * If action name is not present => 404 Not found
     * If action name is present but doesn't contain the method => 405 Method not allowed
     * Otherwise we have a method and we can invoke it
     *
     * Method name must be public and on this class
     *
     * @return array[]
     */
    abstract public static function actions(): array;
}