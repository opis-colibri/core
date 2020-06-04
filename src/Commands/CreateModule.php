<?php
/* ===========================================================================
 * Copyright 2019-2020 Zindex Software
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

namespace Opis\Colibri\Commands;

use Opis\Stream\Wrapper\PHPCodeStreamWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\{convertToCase, info};

class CreateModule extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('create-module')
            ->setDescription('Create local module')
            ->addArgument('module', InputArgument::REQUIRED, "Module's name")
            ->addOption('title', null,InputOption::VALUE_REQUIRED, "Module's title")
            ->addOption('description', null,InputOption::VALUE_REQUIRED, "Module's description")
            ->addOption('installer', null,InputOption::VALUE_NONE, "Create installer class")
            ->addOption('assets', null,InputOption::VALUE_NONE, "Create assets folder");
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('b-info', new OutputFormatterStyle('green', null, ['bold']));

        $module = trim($input->getArgument('module'));
        $regex = '/^[a-z][a-z0-9]*(\-[a-z0-9]+)*$/';

        if (!preg_match($regex, $module)) {
            $output->writeln('<error>Invalid module name</error>');
            return 1;
        }

        $dir = $this->getRepoDir() . '/' . $module;

        if (is_dir($dir)) {
            $output->writeln('<error>Module already exists</error>');
            return 1;
        }

        if (!@mkdir($dir, 0775)) {
            $output->writeln('<error>Could not create directory</error>');
            return 1;
        }

        $vendor = 'local';
        $namespace = convertToCase($vendor, 'PascalCase', 'kebab-case')
            . '\\' .convertToCase($module, 'PascalCase', 'kebab-case');

        if (null === $title = $input->getOption('title')) {
            $title = ucfirst(implode(' ', explode('-', $module)));
        }

        $description = $input->getOption('description') ?? 'Local module';

        $assets = $input->getOption('assets');
        $installer = $input->getOption('installer');

        $args = [
            'vendor' => $vendor,
            'module' => $module,
            'title' => trim(json_encode($title), '"'),
            'description' => trim(json_encode($description), '"'),
            'namespace' => trim(json_encode($namespace), '"'),
            'assets' => $assets,
            'installer' => $installer,
        ];

        $data = $this->template(__DIR__ . '/../../templates/composer.json.php', $args);

        if (!file_put_contents($dir . '/composer.json', $data)) {
            $output->writeln('<error>Unable to create composer.json file</error>');
            return 1;
        }

        if (!@mkdir($dir . '/src', 0775)) {
            $output->writeln('<error>Could not create "src" directory</error>');
            return 1;
        }

        $data = $this->template(__DIR__ . '/../../templates/Collector.php', ['namespace' => $namespace]);

        if (!file_put_contents($dir . '/src/Collector.php', $data)) {
            $output->writeln('<error>Unable to create Collector.php file</error>');
            return 1;
        }

        if ($installer) {
            $data = $this->template(__DIR__ . '/../../templates/Installer.php', ['namespace' => $namespace]);

            if (!file_put_contents($dir . '/src/Installer.php', $data)) {
                $output->writeln('<error>Unable to create Installer.php file</error>');
                return 1;
            }
        }


        if ($assets) {
            if (!@mkdir($dir . '/assets', 0775)) {
                $output->writeln('<error>Could not create assets directory</error>');
                return 1;
            }

            $data = $this->template(__DIR__ . '/../../templates/package.json.php', ['vendor' => $vendor, 'module' => $module]);

            if (!file_put_contents($dir . '/assets/package.json', $data)) {
                $output->writeln('<error>Unable to create package.json file</error>');
                return 1;
            }
        }

        $output->writeln('<info>Module <b-info>local/'. $module .'</b-info> was successfully created</info>');
        $output->writeln('<info>Use the <b-info>composer require local/' . $module . '</b-info> command for using it within your project</info>');

        return 0;
    }

    /**
     * @return string|null
     */
    private function getRepoDir(): ?string
    {
        $composer = json_decode(file_get_contents(info()->composerFile()), true);
        if (!isset($composer['repositories']['local'])) {
            return null;
        }
        $file = realpath(info()->rootDir() . '/' . trim($composer['repositories']['local']['url'], '*/'));

        return $file ? $file : null;
    }

    /**
     * @param $file
     * @param array $args
     * @return string
     */
    private function template($file, array $args): string
    {
        return PHPCodeStreamWrapper::template(file_get_contents($file), $args);
    }
}