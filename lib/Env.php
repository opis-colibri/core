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

    /**
     * APP_INSTALLED
     *
     * @return boolean
     */
    public function appInstalled()
    {
        if (false === $value = getenv('APP_INSTALLED')) {
            return false;
        }

        return $value === 'true';
    }

    /**
     * APP_DEBUG
     *
     * @return boolean
     */
    public function appDebug()
    {
        if (false === $value = getenv('APP_DEBUG')) {
            return false;
        }

        return $value === 'true';
    }

    /**
     * APP_ENV
     *
     * @return string
     */
    public function appEnv()
    {
        if (false === $value = getenv('APP_ENV')) {
            return 'local';
        }

        return $value;
    }


    /**
     * DB_DSN
     *
     * @return mixed
     */
    public function databaseDSN()
    {
        if (false === $value = getenv('DB_DSN')) {
            return false;
        }

        return $value;
    }

    /**
     * DB_USER
     * @return string
     */
    public function databaseUser()
    {
        if (false === $value = getenv('DB_USER')) {
            return 'root';
        }

        return $value;
    }

    /**
     * DB_PASS
     * @return string
     */
    public function databasePass()
    {
        if (false === $value = getenv('DB_PASS')) {
            return '';
        }

        return $value;
    }


    /**
     * DB_STORAGE
     *
     * @return string
     */
    public function databaseStorage()
    {
        if (false === $value = getenv('DB_STORAGE')) {
            return 'ephemeral';
        }

        return $value;
    }

    public function cacheStorage()
    {
        if (false === $value = getenv('CACHE_STORAGE')) {
            return 'ephemeral';
        }

        return $value;
    }

    public function configStorage()
    {
        if (false === $value = getenv('CONFIG_STORAGE')) {
            return 'ephemeral';
        }

        return $value;
    }

}
