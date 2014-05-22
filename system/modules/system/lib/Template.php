<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

namespace Colibri\Module\System;

class Template
{
    
    public function html()
    {
        return <<<'TEMPLATE'
<!DOCTYPE html>
<html>
    <head>
        <?php if(isset($title)): ?>
        <title><?php print $title;?></title>
        <?php endif; ?>
        <?php if(isset($icon)): ?>
        <link href="<?php print $icon;?>" rel="shortcut icon">
        <?php endif; ?>
        <?php print $meta; ?>
        <?php print $styles; ?>
        <?php print $scripts; ?>
    </head>
    <body>
        <?php print $content; ?>
    </body>
</html>
TEMPLATE;
    }
    
    public function attributes()
    {
        return <<<'TEMPLATE'
<?php
$list = array();

foreach($attributes as $attribute => $value)
{
    if($value === null)
    {
        $list[] = $attribute;
    }
    else
    {
        $list[] = $attribute . '="' . $value . '"';
    }
}

print implode(' ', $list);
TEMPLATE;

    }
    
    public function alerts()
    {
        return <<<'TEMPLATE'
<?php if($hasAlerts): ?>
<div class="system-alerts">

    <?php $messages = array(
        'alert-info' => $info,
        'alert-success' => $success,
        'alert-warning' => $warning,
        'alert-danger' => $error,
    );?>
    
    <?php foreach($messages as $class => $type): ?>
        <?php if($type): ?>
        <div class="alert <?php print $class;?>">
            <?php if($dismissable): ?>
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <?php endif; ?>
            <ul>
            <?php foreach($type as $message): ?>
                <li>
                    <?php print $message; ?>
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
    
    public function collection()
    {
        return <<<'TEMPLATE'
<?php foreach($items as $item){print $item;}
TEMPLATE;
    }
    
    public function meta()
    {
        return <<<'TEMPLATE'

<meta <?php print $attributes;?> <?php if($xhtml) print '/';?>>
TEMPLATE;
    }
    
    public function css_link()
    {
        return <<<'TEMPLATE'

<link rel="stylesheet" type="text/css" href="<?php print $href;?>">
TEMPLATE;
    }
    
    public function css_style()
    {
        return <<<'TEMPLATE'

<style type="text/css"<?php if(isset($media)) print ' media="'.$media.'"';?>>
    <?php print $content; ?>

</style>
TEMPLATE;
    }
    
    public function script()
    {
        return <<<'TEMPLATE'

<script <?php print $attributes;?>><?php if(isset($content)) print $content; ?></script>
TEMPLATE;
    }
}
