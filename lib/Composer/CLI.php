<?php

namespace Opis\Colibri\Composer;

use Opis\Colibri\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application as ComposerConsole;

/**
 * Description of Composer
 *
 * @author mari
 */
class CLI
{
    protected $app;
    protected $instance;
    
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected function instance()
    {
        if ($this->instance === null) {
            $this->instance = new ComposerConsole();
        }
        
        return $this->instance;
    }

    public function execute(array $command)
    {
        $this->instance()->run(new ArrayInput($command));
    }
    
    public function dumpAutoload()
    {
        $this->execute(array(
            'command' => 'dump-autoload',
        ));
    }
}
