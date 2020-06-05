<?php
/* ===========================================================================
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

namespace Opis\Colibri\Templates;

class HttpErrors
{
    /**
     * @return string
     */
    public static function error401(): string
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error403(): string
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error404(): string
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error405(): string
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error500(): string
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error503(): string
    {
        return static::error();
    }

    public static function handleError($error): string
    {
        return TemplateStream::url('callback', self::class . '::error' . $error, 'php');
    }

    /**
     * @return string
     */
    public static function error(): string
    {
        return <<<'TEMPLATE'
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?= $status ?> <?= $message ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style type="text/css">
            html, body{
                height: 100%;
                margin: 0;
                padding: 0;
            }
            body{
                background: #fefefe;
                color: #333;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            h1{
                margin: 0;
                font-size: 4em;
                color: #00b5c2;
            }

            h1 small{
                font-size: 0.5em;
                color: #999;
                font-weight: normal;
                text-align: right;
                vertical-align: middle;
            }
            
            #message{
                width: 100%;
                max-width: 700px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            #message > svg {
               max-width: 25%;
            }
        </style>
    </head>
    <body>
        <div id="message">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 355.5274 358.2768">
              <g>
                <path style="fill: currentColor" d="M354.4624,160.5968c-9.8242,66.147-64.2078,119.6167-128.16,124.7469,37.04-15.7055,
                68.5547-50.4588,76.251-88.6856,6.83-33.93,2.6767-72.8387-18.4434-97.0587,27.5752,91-35.1372,
                191.67-159.7363,144.0907l-16.2375,51.827C49.22,243.69,59.1853,187.3356,67.6169,
                148.0492c-31.7334-7.1551-52.7013-24.7415-56.921-27.7816-3.5962,9.57-6.92,24.5512-7.6538,
                28.2892-12.58,64.0574,14.7932,130.4169,64.7731,171.6,109.9465,90.5948,287.7,
                9.23,287.7-133.88C355.5147,177.7167,355.7222,169.0668,354.4624,160.5968ZM171.4831,
                293.1225c.1368.0216.2745.0373.4112.0582A1.7449,1.7449,0,0,1,171.4831,293.1225Z"/>
                <path style="fill: currentColor;" d="M296.5118,45.96s-55.3268-.2333-71.4935,8.7667c-12.3027,6.8489-9.24,
                23.4027-13,37.6174-19.6286,74.1972-77,31.0493-106.4665,113.496-2.3753,6.646-8.1927,
                27.73-7.91,33.2016-.08-1.5548-14.9567-75.6483,22.19-115.6716a123.2472,123.2472,
                0,0,1-15.7,1q-5.6557,0-11.18-.51a120.8546,120.8546,0,0,1-60.73-23.04,122.108,122.108,0,0,
                1-30.98-33.29h104.31c-5.6-1.82-50.48-42-51.79-67.53l97.64,53.11c2.52,1.31,13.11-37.95,
                45.12-34.95.69,0,1.39.03,2.08.08,11.4.78,26.6827,8.9124,30.8223,19.0723Z"/>
              </g>
            </svg>
            <h1><?= $status ?> <small><?= $message ?></small></h1>
        </div>
    </body>
</html>
TEMPLATE;
    }
}