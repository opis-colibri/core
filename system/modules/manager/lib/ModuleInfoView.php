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

class ModuleInfoView extends View
{
    
    protected $module;
    
    protected $form;
    
    public function __construct($module, $form = 'form-module-manager')
    {
        $this->module = $module;
        $this->form = $form;
        parent::__construct('manager.module.info');
        $this->arguments = null;
    }
    
    protected function processModuleList(array $list)
    {
        $result = array();
        
        foreach($list as $module)
        {
            if(Module::exists($module))
            {
                if(Module::isEnabled($module))
                {
                    $status = 'enabled';
                    $statusClass = 'text-success';
                }
                elseif(Module::isInstalled($module))
                {
                    $status = 'disabled';
                    $statusClass = 'text-primary';
                }
                else
                {
                    $status = 'uninstalled';
                    $statusClass = 'text-warning';
                }
            }
            else
            {
                $status = 'missing';
                $statusClass = 'text-danger';
            }
            
            $result[$module] = array(
                'status' => $status,
                'class' => $statusClass
            );
        }
        
        return $result;
    }
    
    public function viewArguments()
    {
        if($this->arguments === null)
        {
            $this->arguments = array(
                'name' => $this->module,
                'title' => Module::title($this->module),
                'description' => Module::description($this->module),
                'buttons' => array(),
                'dependencies' => $this->processModuleList(Module::dependencies($this->module)),
                'dependents' => $this->processModuleList(Module::dependents($this->module)),
            );
            
            if(Module::isInstalled($this->module))
            {
                $this->arguments['status'] = Module::isEnabled($this->module) ? 'enabled' : 'disabled';
                
                if($this->arguments['status'] == 'enabled')
                {
                    $this->arguments['statusClass'] = 'alert-success';
                    
                    $this->arguments['buttons'][] = array(
                        'name' => 'module['.$this->module.']',
                        'value' => 'Disable',
                        'class' => 'btn-warning',
                        'form' => $this->form,
                        'disabled' => !Module::canBeDisabled($this->module),
                    );
                    
                }
                else
                {
                    if(Module::canBeUninstalled($this->module))
                    {
                        $this->arguments['buttons'][] = array(
                            'name' => 'module['.$this->module.']',
                            'value' => 'Uninstall',
                            'class' => 'btn-danger',
                            'form' => $this->form,
                            'disabled' => false,
                        );
                    }
                    
                    if(Module::canBeEnabled($this->module))
                    {
                        $this->arguments['buttons'][] = array(
                            'name' => 'module['.$this->module.']',
                            'value' => 'Enable',
                            'class' => 'btn-success',
                            'form' => $this->form,
                            'disabled' => false,
                        );
                    }
                    
                    $this->arguments['statusClass'] = 'alert-warning';
                }
                
            }
            else
            {
                $this->arguments['status'] = 'uninstalled';
                $this->arguments['statusClass'] = 'alert-danger';
                $this->arguments['buttons'][] = array(
                    'name' => 'module['.$this->module.']',
                    'value' => 'Install',
                    'class' => 'btn-primary',
                    'form' => $this->form,
                    'disabled' => !Module::canBeInstalled($this->module),
                );
            }
        }
        
        return $this->arguments;
    }
    
}
