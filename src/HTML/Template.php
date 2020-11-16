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

namespace Opis\Colibri\HTML;

final class Template
{
    private function __construct()
    {
        // nop
    }

    public static function attributes()
    {
        return <<<'TEMPLATE'
        <?php
        $list = [''];
        foreach ($attributes as $attribute => $value) {
            if ($value === null) {
                $list[] = $attribute;
            } else {
                $list[] = $attribute . '="' . htmlspecialchars($value) . '"';
            }
        }
        echo implode(' ', $list);
        TEMPLATE;
    }

    public static function collection()
    {
        return <<<'TEMPLATE'
        <?php foreach($items as $item){echo $item;}
        TEMPLATE;
    }

    public static function meta()
    {
        return <<<'TEMPLATE'

        <meta<?= $attributes ?>>
        TEMPLATE;
    }

    public static function link()
    {
        return <<<'TEMPLATE'

        <link<?= $attributes ?>>
        TEMPLATE;
    }

    public static function style()
    {
        return <<<'TEMPLATE'
        
        <style type="text/css"<?php if(isset($media)) print ' media="'.$media.'"';?>>
        <?= $content ?>
        
        </style>
        TEMPLATE;
    }

    public static function script()
    {
        return <<<'TEMPLATE'
        
        <script<?= $attributes ?>><?= $content ?? '' ?></script>
        TEMPLATE;
    }

    public static function document()
    {
        return <<<'TEMPLATE'
        <!DOCTYPE html>
        <html<?= $htmlAttributes ?? '' ?>>
        <head>
        <?php if(isset($base)): ?><base href="<?= $base ?>"><?php endif ?>
        <?php if(isset($title)): ?><title><?= $title ?></title><?php endif; ?>
        <?= $meta ?>
        <?= $links ?>
        <?= $styles ?>
        <?= $scripts->headScripts() ?>
        </head>
        <body<?= $bodyAttributes ?? '' ?>>
        <?= $content ?>
        <?= $scripts->bodyScripts() ?>
        </body>
        </html>
        TEMPLATE;
    }
}
