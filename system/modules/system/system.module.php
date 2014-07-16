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

use Opis\Colibri\Define;

Define::Dispatchers(function($dispatcher){
    
    $dispatcher->register('html', function(){
        return new Colibri\Module\System\HtmlDispatcher();
    });
    
});

Define::Views(function($view){
    
    $view->handle('html.{suggestion?}', function(){
        return 'template://Colibri\Module\System\Template@html';
    }, -100)
    ->where('suggestion', '.+');
    
    $view->handle('css.{type?}', function($type){
        return 'template://Colibri\Module\System\Template@css_' . $type;
    }, -100)
    ->implicit('type', 'style')
    ->where('type', 'link|style');
    
    $view->handle('{view}', function($view){
        return 'template://Colibri\Module\System\Template@' . $view;
    }, -100)
    ->where('view', 'collection|script|attributes|meta|alerts');
    
    $view->handle('error.{error}', function($error){
        return __DIR__ . '/view/error' . $error . '.php';
    })
    ->where('error', '404|403');
    
});


Define::EventHandlers(function($handler){
    
    $handler->handle('system.init', function(){
        Colibri\Module\System\TemplateStream::register();
    });
    
});

Define::Contracts(function($contract){
    
    $contract->singleton('Colibri\\Module\\System\\Views\\Alerts');
    $contract->alias('Colibri\\Module\\System\\Views\\Alerts', 'SystemAlerts');
    
});

