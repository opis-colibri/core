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
        if ($this->instance === null) {
            $this->instance = new ComposerConsole();
            $this->instance->setAutoExit(false);
        }

        return $this->instance;
    }

    public function execute(array $command)
    {
        $this->instance()->run(new ArrayInput($command), new NullOutput());
    }

    public function dumpAutoload()
    {

        return $this->execute(array(
            'command' => 'dump-autoload',
        ));

        $input = new ArrayInput([
            'command' => 'dump-autoload',
        ]);

        $output = new NullOutput();

        $composer = $this->app->getComposer();
        $commandEvent = new CommandEvent(PluginEvents::COMMAND, 'dump-autoload', $input, $output);
        $composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);

        $installationManager = $composer->getInstallationManager();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $package = $composer->getPackage();
        $config = $composer->getConfig();

        $optimize = $config->get('optimize-autoloader');
        $authoritative = $config->get('classmap-authoritative');


        $generator = $composer->getAutoloadGenerator();
        $generator->setDevMode(true);
        $generator->setClassMapAuthoritative($authoritative);
        $generator->setRunScripts(true);
        $generator->dump($config, $localRepo, $package, $installationManager, 'composer', $optimize);


    }
}
