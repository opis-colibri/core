<?php

namespace Opis\Colibri\Composer;

use Composer\Composer;
use Composer\Console\Application as ComposerConsole;
use Opis\Colibri\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Symfony\Component\Console\Output\NullOutput;

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
        $instance = new ComposerConsole();
        $instance->setAutoExit(false);
        return $instance;
    }

    public function execute(array $command)
    {
        $cwd = getcwd();
        chdir($this->app->getAppInfo()->rootDir());
        $this->instance()->run(new ArrayInput($command), new NullOutput());
        chdir($cwd);
    }

    public function dumpAutoload()
    {
        $this->execute(array(
            'command' => 'dump-autoload',
        ));
    }
}
