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

/*
 * Use this file with the PHP's built in server.
 * Just type the following command and point your browser to http://localhost:8080/
 *
 * php - S localhost:8080 -t public router.php
 */

if(false !== $pos = strpos($_SERVER['REQUEST_URI'], '?'))
{
    $len = strlen($_SERVER['REQUEST_URI']);
    $file = $_SERVER['DOCUMENT_ROOT'] . substr($_SERVER['REQUEST_URI'], 0, $len - ($len - $pos));
}
else
{
    $file = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
}

if (file_exists($file))
{
    if(preg_match('/\.(eot|ttf|svg|woff|woff2)$/', $file, $extension))
    {
        $mimetypes = array(
            'eot' => 'application/vnd.ms-fontobject',
            'woff' => 'application/x-font-woff',
            'woff2' => 'application/x-font-woff',
            'ttf' => 'application/x-font-ttf',
            'svg' => 'image/svg+xml',
        );
        
        header('Content-Type: ' . $mimetypes[$extension[1]]);
        readfile($file);
        
        return;
    }
    
    return false;
}

require __DIR__ . '/public/index.php';
