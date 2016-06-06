<?php

namespace Opis\Colibri;

use Dotenv\Dotenv;
/**
 * Description of Env
 *
 * @author mari
 */
class Env
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        
        if (file_exists($app->info()->vendorDir() . '/.env')) {
            $env = new Dotenv($app->info()->vendorDir());
        } else {
            $env = new Dotenv($app->info()->rootDir());
        }
        
        $env->load();
    }
    
    public function appInstalled()
    {
        if (false === $value = getenv('APP_INSTALLED')) {
            return false;
        }
        
        return $value === 'true';
    }
    
    public function appDebug()
    {
        if (false === $value = getenv('APP_DEBUG')) {
            return false;
        }
        
        return $value === 'true';
    }
    
    public function appEnv()
    {
        if (false === $value = getenv('APP_ENV')) {
            return 'local';
        }
        
        return $value;
    }
    
}
