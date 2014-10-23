<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

namespace Colibri\Module\System\Views;

use Opis\Colibri\View;

class Alerts extends View
{
    
    public function __construct()
    {
        parent::__construct('alerts', array(
            'error' => $this->getFlashData('error'),
            'warning' => $this->getFlashData('warning'),
            'success' => $this->getFlashData('success'),
            'info' => $this->getFlashData('info'),
            'hasAlerts' => false,
            'dismissable' => false,
        ));
    }
    
    
    protected function getFlashData($type)
    {
        $type = 'system_' . $type;
        
        if(!Session()->flash()->has($type))
        {
            Session()->flash()->set($type, array());
        }
        
        $data = Session()->flash()->get($type, array());
        
        return $data;
        
    }
    
    protected function hasAlerts()
    {
        foreach(array('info', 'error', 'warning', 'success') as $key)
        {
            if(!empty($this->arguments[$key]))
            {
                return true;
            }
        }
        
        return false;
    }
    
    protected function setFlashData($type, $message)
    {
        $type = 'system_' . $type;
        
        $data = $this->getFlashData($type);
        $data[] = $message;
        
        Session()->flash()->set($type, $data);
    }
    
    protected function alert($type, $message)
    {   
        $this->arguments[$type][] = $message;
        
        $this->setFlashData($type, $message);
        
        return $this;
    }
    
    public function viewArguments()
    {
        $this->arguments['hasAlerts'] = $this->hasAlerts();
        return parent::viewArguments();
    }
    
    public function hasErrors()
    {
        $errors = $this->getFlashData('error');
        return !empty($errors);
    }
    
    public function hasWarnings()
    {
        $errors = $this->getFlashData('warning');
        return !empty($errors);
    }
    
    public function dismissable($value = true)
    {
        $this->arguments['dismissable'] = $value;
        return $this;
    }
    
    public function error($message)
    {
        return $this->alert('error', $message);
    }
    
    public function warning($message)
    {
        return $this->alert('warning', $message);
    }
    
    public function success($message)
    {
        return $this->alert('success', $message);
    }
    
    public function info($message)
    {
        return $this->alert('info', $message);
    }
    
}
