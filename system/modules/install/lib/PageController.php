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

namespace Colibri\Module\Install;

use Exception;
use Opis\Colibri\App;
use Opis\Colibri\Module;
use Opis\Database\Connection;
use Opis\Config\Config;
use Opis\Config\Storage\PHPFile as ConfigFileStorage;

class PageController
{
    
    
    public function index()
    {
        return array(
            'title' => 'Welcome',
            'content' => View('install.page.welcome', array(
                'jumbotron' => View('install.jumbotron', array(
                    'title' => 'A different kind of framework',
                    'description' => 'Build and deploy your web application in minutes.',
                    'logo' => Asset('system', 'img/opis-colibri.png'),
                    'button' => array(
                        'link' => UriForPath('/install/requirements'),
                        'text' => 'Install Opis Colibri',
                    ),
                )),
            ))
        );
        
    }
    
    public function requirements()
    {
        $messages = array(
            Check::pdo(),
            Check::assets(),
        );
        
        $messages = array_merge($messages, Check::storages());
        $errors = Check::hasErrors();
        
        $jumbotron =  array(
            'title' => 'Verify requirements',
            'description' => 'Fix all issues marked in red, then refresh this page.',
            'logo' => Asset('system', 'img/opis-colibri.png'),
            'button' => array(
                'link' => '',
                'text' => 'Reload page',
            ),
        );
        
        if(!$errors)
        {
            $jumbotron['description'] = 'Green light! You may proceed to the next step.';
            $jumbotron['button'] = array(
                'link' => UriForPath('/install/database'),
                'text' => 'Next step',
            );
        }
        
        return array(
            'title' => 'Requirements',
            'content' => View('install.page.requirements', array(
                'messages' => $messages,
                'errors' => $errors,
                'jumbotron' => View('install.jumbotron', $jumbotron),
            )),
        );
    }
    
    public function database()
    {   
        $data = Session()->get('database', array(
            'database' => null,
            'username' => null,
            'password' => null,
            'host' => 'localhost',
            'port' => 3306,
        ));
        
        $data['alerts'] = Using('SystemAlerts');
        $data['jumbotron'] = View('install.jumbotron', array(
            'title' => 'Database connection',
            'description' => 'Setup your default database connection.',
            'logo' => Asset('system', 'img/opis-colibri.png'),
            'button' => array(
                'link' => UriForPath('/install/account'),
                'text' => 'Skip this step',
            ),
        ));
        
        return array(
            'title' => 'Database',
            'content' => View('install.page.database', $data),
        );
    }
    
    public function submitDatabase()
    {
        $request = HttpRequest();
        
        $database = trim($request->post('database', ''));
        $username = trim($request->post('username', ''));
        $password = trim($request->post('password', ''));
        $host = trim($request->post('host', 'localhost'));
        $port = trim($request->post('port', '3306'));
        
        $check = array(
            'Database' => $database,
            'Username' => $username,
            'Host' => $host,
            'Port' => $port,
        );
        
        foreach($check as $key => $value)
        {
            if($value === '')
            {
                Using('SystemAlerts')->error($key . ' is required');
            }
        }
        
        if(!Using('SystemAlerts')->hasErrors())
        {
            try
            {
                Connection::mysql($username, $password)
                            ->database($database)
                            ->host($host)
                            ->port($port)
                            ->pdo();
            }
            catch(Exception $e)
            {
                Using('SystemAlerts')->error($e->getMessage());
            }
        }
        
        $data = array(
            'database' => $database,
            'username' => $username,
            'password' => $password,
            'host' => $host,
            'port' => $port,
        );
        
        Session()->remember('database', $data);
        
        if(!Using('SystemAlerts')->hasErrors())
        {
            Session()->remember('database-ok', true);
            
            HttpRedirect(UriForPath('/install/account'));
            
            $content = file_get_contents(COLIBRI_SYSTEM_MODULES_PATH . '/install/stubs/site');
            
            foreach($data as $key => $value)
            {
                $content = str_replace('{$'.$key.'}', "'" . $value . "'", $content);
            }
            
            file_put_contents(COLIBRI_STORAGES_PATH . '/site.inc', $content);
            
            $conf = new Config(new ConfigFileStorage(COLIBRI_STORAGES_PATH . '/config', 'system'));
            
            $conf->write('modules', Config()->read('modules'));
            $conf->write('collectors', Config()->read('collectors'));
            
        }
        
        $data['alerts'] = Using('SystemAlerts');
        $data['jumbotron'] = View('install.jumbotron', array(
            'title' => 'Database connection',
            'description' => 'Setup your default database connection.',
            'logo' => Asset('system', 'img/opis-colibri.png'),
            'button' => array(
                'link' => UriForPath('/install/account'),
                'text' => 'Skip this step',
            ),
        ));
        
        return array(
            'title' => 'Database',
            'content' => View('install.page.database', $data),
        );
        
    }
    
    public function account()
    {
        
        $data = Session()->get('account', array(
            'username' => '',
            'password' => '',
            'check' => '',
        ));
        
        $data['alerts'] = Using('SystemAlerts');
        $data['jumbotron'] = View('install.jumbotron', array(
            'title' => 'Account setup',
            'description' => 'Setup your administrator account.',
            'logo' => Asset('system', 'img/opis-colibri.png'),
            'button' => array(
                'link' => UriForPath('/install/finish'),
                'text' => 'Skip this step',
            ),
        ));
        return array(
            'title' => 'Account',
            'content' => View('install.page.account', $data),
        );
    }
    
    public function submitAccount()
    {
        $request = HttpRequest();
        
        $username = trim($request->post('username', ''));
        $password = trim($request->post('password', ''));
        $check = trim($request->post('check'));
        
        if($username === '')
        {
            Using('SystemAlerts')->error('Username is rquired');
        }
        if($password === '')
        {
            Using('SystemAlerts')->error('Password is rquired');
        }
        elseif($password !== $check)
        {
            Using('SystemAlerts')->error('Password must match');
        }
        
        $data = array(
            'username' => $username,
            'password' => $password,
            'check' => $check,
        );
        
        Session()->remember('account', $data);
        
        if(!Using('SystemAlerts')->hasErrors())
        {
            Session()->remember('account-ok', true);
            HttpRedirect(UriForPath('/install/finish'));
        }
        
        $data['alerts'] = Using('SystemAlerts');
        
        $data['jumbotron'] = View('install.jumbotron', array(
            'title' => 'Account setup',
            'description' => 'Setup your administrator account.',
            'logo' => Asset('system', 'img/opis-colibri.png'),
            'button' => array(
                'link' => UriForPath('/install/finish'),
                'text' => 'Skip this step',
            ),
        ));
        return array(
            'title' => 'Account',
            'content' => View('install.page.account', $data),
        );
        
    }
    
    
    
    public function finish()
    {
        $database = Session()->get('database-ok', false);
        $account = Session()->get('account-ok', false);
        $path = substr(COLIBRI_ROOT, strlen(dirname(COLIBRI_ROOT)));
        if($database)
        {
            $content = file_get_contents(COLIBRI_SYSTEM_MODULES_PATH . '/install/stubs/site-db');
            
            foreach(Session()->get('database') as $key => $value)
            {
                $content = str_replace('{$'.$key.'}', "'$value'", $content);
            }
        }
        else
        {
            $content = file_get_contents(COLIBRI_SYSTEM_MODULES_PATH . '/install/stubs/site');
        }
        
        $data = array(
            'code' => highlight_string($content, true),
            'path' => $path,
            'fullpath' => COLIBRI_ROOT,
            'adminpath' => UriForPath('/module-manager'),
        );
        
        $data['jumbotron'] = View('install.jumbotron', array(
            'title' => 'Great news!',
            'description' => 'Your new website is just one click away.',
            'logo' => Asset('system', 'img/opis-colibri.png'),
            'button' => array(
                'text' => 'Create my site',
            ),
        ));
        
        return array(
            'title' => 'Finish',
            'content' => View('install.page.finish', $data),
        );
    }
    
    public function submitFinish()
    {
        if(Session()->get('database-ok', false))
        {
            $content = file_get_contents(COLIBRI_SYSTEM_MODULES_PATH . '/install/stubs/site-db');
            
            foreach(Session()->get('database') as $key => $value)
            {
                $content = str_replace('{$'.$key.'}', "'$value'", $content);
            }
        }
        else
        {
            $content = file_get_contents(COLIBRI_SYSTEM_MODULES_PATH . '/install/stubs/site');
        }
        
        $conf = new Config(new ConfigFileStorage(COLIBRI_STORAGES_PATH . '/config', 'system'));
        
        //Disable system module
        Module::disable('system', false);
        
        //Remove install module
        Module::disable('install', false);
        Module::uninstall('install', false);
        
        //Enable system module 
        Module::enable('system', false);
        
        if(Session()->get('account-ok', false))
        {
            
            //Enable manager module
            Module::install('manager', false);
            Module::enable('manager', false);
            
            $account = Session()->get('account');
            
            $conf->write('admin', array(
                'username' => $account['username'],
                'password' => md5($account['password']),
            ));
            
        }
        
        //Enable welcome module
        Module::install('welcome', false);
        Module::enable('welcome');
        
        $conf->write('modules', Config()->read('modules'));
        $conf->write('collectors', Config()->read('collectors'));
        
        file_put_contents(COLIBRI_STORAGES_PATH . '/site.php', $content);
        Session()->dispose();
        HttpRedirect(UriForPath('/'));
    }
    
}
