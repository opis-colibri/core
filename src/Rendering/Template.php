<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

namespace Opis\Colibri\Rendering;

class Template
{
    /**
     * @return string
     */
    public static function error401()
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error403()
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error404()
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error405()
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error500()
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function error503()
    {
        return static::error();
    }

    /**
     * @return string
     */
    public static function alerts()
    {
        return <<<'TEMPLATE'
<?php if($has_alerts): ?>
<?php $messages = [
    'alert-info' => $info,
    'alert-success' => $success,
    'alert-warning' => $warning,
    'alert-danger' => $error,
];?>
<div class="opis-colibri-alerts">
<?php foreach($messages as $class => $type): ?>
    <?php if($type): ?>
        <div class="alert <?= $class ?>" role="alert">
            <?php if($dismissible): ?>
           <button type="button" class="close" data-dismiss="alert" aria-label="Close">
             <span aria-hidden="true">&times;</span>
           </button>
            <?php endif; ?>
            <ul>
            <?php foreach($type as $message): ?>
                <li>
                    <?= $message ?>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
<?php endforeach;?>
</div>
<?php endif; ?>
TEMPLATE;
    }

    /**
     * @return string
     */
    public static function error()
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
            #message > img {
               max-width: 25%;
            }
        </style>
    </head>
    <body>
        <div id="message">
            <img src="<?= $logo ?>">
            <h1><?= $status ?> <small><?= $message ?></small></h1>
        </div>
    </body>
</html>
TEMPLATE;
    }
}