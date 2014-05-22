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

use Opis\Colibri\Module;
use Opis\Colibri\App;

class PageController
{
    
    public function index()
    {
        $menu = View('manager.menu', array(
            'links' => array(
                'Home' => array(
                    'class' => 'fa fa-home fa-lg',
                    'title' => 'Back to site',
                    'href' => UriForPath('/'),
                ),
                'Settings' => array(
                    'class' => 'fa fa-wrench fa-lg',
                    'title' => 'Account setup',
                    'href' => UriForPath('/module-manager/setup'),
                ),
            ),
            'collect' => array(
                'title' => 'Collect resources',
                'action' => UriForPath('/module-manager'),
            ),
        ));
        
        return array(
            'title' => 'Module manager',
            'menu' => $menu,
            'content' => View('manager.module.manage', array(
                'list' => new ModuleListView(),
                'alerts' => Using('SystemAlerts')->dismissable(),
            )),
        );
        
    }
    
    public function submitIndex()
    {
        if(HttpRequest()->post('action') === 'Logout')
        {
            Session()->forget('is_system_admin');
            Using('SystemAlerts')->success('You have been logged out.');
            HttpRedirect(UriForPath('/module-manager/login'), 303);
        }
        
        if(App::systemCache()->clear())
        {
            Using('SystemAlerts')->success('Resources have been collected');
        }
        else
        {
            Using('SystemAlerts')->error('Faild when collecting resources');
        }
        
        HttpRedirect(UriForPath('/module-manager'), 303);
    }
    
    public function module()
    {
        return null;
    }
    
    public function submitModule()
    {
        foreach(HttpRequest()->post('module') as $module => $action)
        {
            $action = strtolower($action);
            
            if(!in_array($action, array('enable', 'disable', 'install', 'uninstall')))
            {
                continue;
            }
            
            if(Module($module)->{$action}())
            {
                Using('SystemAlerts')->success("Module <strong>$module</strong> was $action" . 'd.');
            }
            else
            {
                Using('SystemAlerts')->error("Module <strong>$module</strong> could not be $action" . 'd.');
            }
        }
        
        HttpRedirect(UriForPath('/module-manager'), 303);
    }
    
    public function login()
    {
        $menu = View('manager.menu', array(
            'links' => array(
                'Home' => array(
                    'class' => 'fa fa-home fa-lg',
                    'title' => 'Back to site',
                    'href' => UriForPath('/'),
                ),
            ))
        );
        
        return array(
            'title' => 'Login',
            'menu' => $menu,
            'content' => View('manager.login',array(
                'alerts' => Using('SystemAlerts'),
                'username' => null,
                'password' => null,
            )),
        );
    }
    
    public function submitLogin()
    {
        $username = trim(HttpRequest()->post('username'));
        $password = trim(HttpRequest()->post('password'));
        
        if($username === '')
        {
            Using('SystemAlerts')->error('Username is required');
        }
        
        if($password === '')
        {
            Using('SystemAlerts')->error('Password is required');
        }
        
        Config()->write('manager', array(
            'username' => 'root',
            'password' => md5('x'),
        ));
        
        if(!Using('SystemAlerts')->hasErrors())
        {
            if($username != Config()->read('manager.username') || md5($password) !== Config()->read('manager.password'))
            {
                Using('SystemAlerts')->error('Invalid username or password');
            }
        }
        
        
        if(!Using('SystemAlerts')->hasErrors())
        {
            Session()->remember('is_system_admin', true);
            Using('SystemAlerts')->success("Hello <strong>$username</strong>. You are now authenticated as system admin");
            HttpRedirect(UriForPath('/module-manager'), 303);
        }
        
        $menu = View('manager.menu', array(
            'links' => array(
                'Home' => array(
                    'class' => 'fa fa-home fa-lg',
                    'title' => 'Back to site',
                    'href' => UriForPath('/'),
                ),
            ))
        );
        
        return array(
            'title' => 'Login',
            'menu' => $menu,
            'content' => View('manager.login',array(
                'alerts' => Using('SystemAlerts'),
                'username' => null,
                'password' => null,
            )),
        );
    }
    
    public function setup()
    {
        
        $menu = array(
            'links' => array(
                'Home' => array(
                    'class' => 'fa fa-home fa-lg',
                    'title' => 'Back to site',
                    'href' => UriForPath('/'),
                ),
            ),
        );
        
        if(Config()->has('manager'))
        {
            $menu['links']['Module list'] = array(
                'class' => 'fa fa-list fa-lg',
                'title' => 'Module list',
                'href' => UriForPath('/module-manager'),
            );
        }
        
        return array(
            'title' => 'Account setup',
            'menu' => View('manager.menu', $menu),
            'content' => View('manager.setup', array(
                'alerts' => Using('SystemAlerts'),
                'username' => null,
                'password' => null,
                'check' => null,
            )),
        );
    }
    
    public function submitSetup()
    {
        $username = trim(HttpRequest()->post('username', ''));
        $password = trim(HttpRequest()->post('password', ''));
        $check = trim(HttpRequest()->post('check', ''));
        
        if($username === '')
        {
            Using('SystemAlerts')->error('Username is required');
        }
        
        if($password === '')
        {
            Using('SystemAlerts')->error('Password is required');
        }
        else
        {
            if($check !== $password)
            {
                Using('SystemAlerts')->error('Passwords must match');
            }
        }
        
        if(!Using('SystemAlerts')->hasErrors())
        {
            Config()->write('manager', array(
                'username' => $username,
                'password' => md5($password),
            ));
            
            App::systemCache()->clear();
            Using('SystemAlerts')->success('Your settings have been saved.');
            HttpRedirect(UriForPath('/module-manager'), 303);
        }
        
        $menu = array(
            'links' => array(
                'Home' => array(
                    'class' => 'fa fa-home fa-lg',
                    'title' => 'Back to site',
                    'href' => UriForPath('/'),
                ),
            ),
        );
        
        if(Config()->has('manager'))
        {
            $menu['links']['Module list'] = array(
                'class' => 'fa fa-list fa-lg',
                'title' => 'Module list',
                'href' => UriForPath('/module-manager'),
            );
        }
        
        return array(
            'title' => 'Account setup',
            'menu' => View('manager.menu', $menu),
            'content' => View('manager.setup', array(
                'alerts' => Using('SystemAlerts'),
                'username' => $username,
                'password' => $password,
                'check' => $check,
            )),
        );
    }
    
}
