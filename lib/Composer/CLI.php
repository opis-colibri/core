<?php

namespace Opis\Colibri\Composer;

use Composer\Composer;
use Composer\Console\Application as ComposerConsole;
use Composer\Factory;
use Composer\IO\NullIO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use function Opis\Colibri\Helpers\{info};

/**
 * Description of Composer
 *
 * @author mari
 */
class CLI
{
    protected $instance;

    public function __construct()
    {
        if(getenv('HOME') === false){
            putenv('HOME=' . posix_getpwuid(fileowner(info()->rootDir()))['dir']);
        }
    }

    public function getComposer(): Composer
    {
        $rootDir = info()->rootDir();
        $composerFile = info()->composerFile();
        return (new Factory())->createComposer(new NullIO(), $composerFile, false, $rootDir);
    }

    public function execute(array $command)
    {
        $cwd = getcwd();
        chdir(info()->rootDir());
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
