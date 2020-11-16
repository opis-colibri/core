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

namespace Test\RestTest\API;

use Opis\Colibri\RestAPI\RestController;
use Opis\Http\Responses\JSONResponse;

class CustomController extends RestController
{
    public function actionGetList(): JSONResponse
    {
        return $this->http200([1, 2, 3]);
    }

    public function actionAddToList($data): JSONResponse
    {
        $error = $this->validate($data, 'json-schema://test.rest-test/add-to-list.json');

        if ($error) {
            return $this->httpValidationError($error);
        }

        return $this->http204();
    }

    /**
     * @inheritDoc
     */
    public static function actions(): array
    {
        return [
            'collection' => [
                'get' => 'actionGetList',
                'post' => 'actionAddToList',
            ],
        ];
    }
}