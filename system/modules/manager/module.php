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

Colibri\Define\Routes(function($route){
    
    
    $callback = function($html, $page)
    {
        $html->icon(Asset('system', 'img/favicon.png'));
        $page['logo'] = Asset('system', 'img/opis.png');
        
        $html->title($page['title'] . ' | Opis Colibri')
             ->content(View('manager.page', $page));
        
        $html->css()
                ->link(Asset('system', 'css/bootstrap.min.css'))
                ->link(Asset('system', 'css/bootstrap-theme.min.css'))
                ->link(Asset('system', 'css/font-awesome.min.css'))
                ->link(Asset('manager', 'style.css'));
                
        $html->script()
                ->link(Asset('system', 'js/jquery-1.11.0.min.js'))
                ->link(Asset('system', 'js/bootstrap.min.js'));
        
    };
    
    
    if(!Config()->has('manager'))
    {
        $route->all('/module-manager', function($method){
            return Using('ManagerPageController')->{$method}();
        })
        ->method(array('GET', 'POST'))
        ->implicit('action', 'setup')
        ->bind('method', function($action, $path){
            return $path->method() == 'GET' ? $action : 'submit' . ucfirst($action);
        })
        ->dispatcher('html')
        ->callback($callback);
        
        return;
    }
    
    $route->filter('manager:is_system_admin', function($path, $route){
        if(Session()->get('is_system_admin', false))
        {
            if($path->path() === '/module-manager/login')
            {
                HttpRedirect(UriForPath('/module-manager'), 303);
            }
        }
        else
        {
            if($path->path() !== '/module-manager/login')
            {
                HttpRedirect(UriForPath('/module-manager/login'), 303);
            }
        }
        return true;
    });
   
    $route->all('/module-manager/{action?}', function($method){
        return Using('ManagerPageController')->{$method}();
    })
    ->method(array('GET', 'POST'))
    ->where('action', 'login|module|setup|logout')
    ->implicit('action', 'index')
    ->bind('method', function($action, $path){
        return $path->method() == 'GET' ? $action : 'submit' . ucfirst($action);
    })
    ->useFilters(array('manager:is_system_admin'))
    ->dispatcher('html')
    ->callback($callback);
    
});

Colibri\Define\Connections(function($connection){
    
    $connection->create('test')
                    ->mysql('root', 'x')
                    ->database('opis.dev')
                    ->charset('utf8')
                    ->persistent();
    
});

Colibri\Define\Views(function($view){
    
    $view->handle('manager.{content}', function($content){
        return __DIR__ . '/view/' . $content . '.php';
    })
    ->where('content', 'page|setup|menu|login');
    
    $view->handle('manager.module.{content}', function($content){
        return __DIR__ . '/view/'. $content .'.php';
    })
    ->where('content', 'info|list|manage');
    
});

Colibri\Define\Contracts(function($contract){
    
    $contract->alias('Colibri\Module\Manager\PageController', 'ManagerPageController');
    
});
