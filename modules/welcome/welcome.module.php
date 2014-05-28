<?php

//Collect routes
Colibri\Define\Routes(function($route){
    
    $route->get('/', function(){
        return View('welcome');
    });
    
});

//Collect views
Colibri\Define\Views(function($view){
    
    $view->handle('welcome', function(){
        return __DIR__ . '/welcome.php';
    });
    
});