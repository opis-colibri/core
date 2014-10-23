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

use Opis\Colibri\Define;
 
Define::Routes(function($route){
    
    $callback = function($html, $page)
    {
        $html->icon(Asset('system', 'img/favicon.png'));
        
        $page['logo'] = Asset('system', 'img/opis.png');
        
        $html->title($page['title'] . ' | Opis Colibri')
             ->content(View('install.page', $page));
        
        $html->css()
                ->link(Asset('system', 'css/bootstrap.min.css'))
                ->link(Asset('system', 'css/bootstrap-theme.min.css'))
                ->link(Asset('system', 'css/font-awesome.min.css'))
                ->link(Asset('install', 'style.css'));
                
        $html->script()
                ->link(Asset('system', 'js/jquery-1.11.0.min.js'))
                ->link(Asset('system', 'js/bootstrap.min.js'));
    };
    
    $route->get('/', function(){
        return Using('InstallPageController')->index();
    })
    ->dispatcher('html')
    ->callback($callback);
    
    $route->get('/install/{page}', function($page){
        return Using('InstallPageController')->{$page}();
    })
    ->where('page', 'requirements|account|finish|database')
    ->dispatcher('html')
    ->callback($callback);
    
    $route->post('/install/{page}', function($page){
        return Using('InstallPageController')->{$page}();
    })
    ->where('page', 'account|database|finish')
    ->bind('page', function($page){
        return 'submit' . ucfirst($page);
    })
    ->dispatcher('html')
    ->callback($callback);
    
    $route->get('/assets/module/{module}/{resource}', function($resource){
        return $resource;
    })
    ->where('resource', '.+')
    ->bind('resource', function($module, $resource){
        
        if(!\Opis\Colibri\Module::exists($module))
        {
            return null;
        }
        
        $path =  Module($module)->assets() . '/' . $resource;
        
        if(file_exists($path))
        {
            return new \Opis\Http\Container\Resource($path);
        }
        
        return null;
        
    });

});


Define::Contracts(function($contract){
    
    $contract->alias('Colibri\Module\Install\PageController', 'InstallPageController');
    
});


Define::Views(function($view){
    
    $view->handle('install.page.{content?}', function($content){
        return __DIR__ . '/view/' . $content . '.php';
    })
    ->where('content', 'welcome|finish|requirements|account|database')
    ->implicit('content', 'page');
    
    $view->handle('install.jumbotron', function(){
       return __DIR__ . '/view/jumbotron.php'; 
    });
    
});
