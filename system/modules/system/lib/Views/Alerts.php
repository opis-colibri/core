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
    protected $dismissable = false;
    
    public function __construct()
    {
        parent::__construct('alerts');
        
        $this->arguments = null;
    }
    
    
    protected function alert($type, $message)
    {
        $type = 'system_' . $type;
        $list = Session()->flash()->get($type, array());
        $list[] = $message;
        Session()->flash()->set($type, $list);
        
        return $this;
    }
    
    public function viewArguments()
    {
        if($this->arguments === null)
        {
            $this->arguments = array(
                'dismissable' => $this->dismissable,
                'hasAlerts' => $this->hasAlerts(),
            );
            
            foreach(array('error', 'warning', 'success', 'info') as $key)
            {
                $type = 'system_' . $key;
                $this->arguments[$key] = Session()->flash()->get($type, array());
                Session()->flash()->delete($type);
            }
            
        }
        
        return $this->arguments;
    }
    
    protected function hasAlerts()
    {
        return ($this->hasErrors() || $this->hasMessages() || $this->hasWarnings() || $this->hasInfos());
    }
    
    public function hasErrors()
    {
        return Session()->flash()->has('system_error');
    }
    
    public function hasWarnings()
    {
        return Session()->flash()->has('system_warning');
    }
    
    public function hasInfos()
    {
        return Session()->flash()->has('system_info');
    }
    
    public function hasMessages()
    {
        return Session()->flash()->has('system_success');
    }
    
    public function dismissable($value = true)
    {
        $this->dismissable = $value;
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
