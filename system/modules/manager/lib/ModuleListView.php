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

namespace Colibri\Module\Manager;

use Opis\Colibri\View;
use Opis\Colibri\Module;

class ModuleListView extends View
{
    
    public function __construct(array $form = array())
    {
        $modules = array();
        
        $form += array(
            'id' => 'form-module-manager',
            'method' => 'post',
            'action' => UriForPath('/module-manager/module'),
        );
        
        foreach(Module::findAll() as $module => $info)
        {
            if(!$info['hidden'])
            {
                $modules[] = new ModuleInfoView($module, $form['id']);
            }
        }
        
        parent::__construct('manager.module.list', array(
            'list' => $modules,
            'form' => $form,
        ));
        
    }
    
}
