<?php

use Opis\Colibri\Define;

//Collect routes
Define::Routes(function($route){
    
    $route->get('/', function(){
        return View('welcome');
    });
    
});

//Collect views
Define::Views(function($view){
    
    $view->handle('welcome', function(){
        return __DIR__ . '/welcome.php';
    });
    
});