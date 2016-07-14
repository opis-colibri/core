<?php

namespace Opis\Colibri\Composer;

use Composer\Composer;
use Composer\Console\Application as ComposerConsole;
use Composer\Factory;
use Composer\IO\NullIO;
use Opis\Colibri\Application;
use Symfony\Component\Console\Input\ArrayInput;
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
        if(getenv('HOME') === false){
            putenv('HOME=' . posix_getpwuid(fileowner($app->getAppInfo()->rootDir()))['dir']);
        }
        $this->app = $app;
    }

    public function getComposer(): Composer
    {
        $rootDir = $this->app->getAppInfo()->rootDir();
        $composerFile = $this->app->getAppInfo()->composerFile();
        return (new Factory())->createComposer(new NullIO(), $composerFile, false, $rootDir);
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

    protected function instance()
    {
        $instance = new ComposerConsole();
        $instance->setAutoExit(false);
        return $instance;
    }
}
