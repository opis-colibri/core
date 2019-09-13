<?php
/* ===========================================================================
 * Copyright 2019 Zindex Software
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\Functions\{app, convertToCase, info};

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
            ->addArgument('module', InputArgument::REQUIRED, 'Module name')
            ->addOption('title', null,InputOption::VALUE_REQUIRED, "Module's title")
            ->addOption('description', null,InputOption::VALUE_REQUIRED, "Module's description")
            ->addOption('namespace', null,InputOption::VALUE_REQUIRED, "Module's namespace")
            ->addOption('assets', null,InputOption::VALUE_REQUIRED, "Create assets folder");
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

        if (null === $namespace = $input->getOption('namespace')) {
            $namespace = convertToCase($vendor, 'PascalCase', 'kebab-case')
                . '\\' .convertToCase($module, 'PascalCase', 'kebab-case');
        }

        if (null === $title = $input->getOption('title')) {
            $title = ucfirst(implode(' ', explode('-', $module)));
        }

        $description = $input->getOption('description') ?? 'Local module';

        $assets = $input->getOption('assets');

        if ($assets !== null && !preg_match($regex, $assets)) {
            $assets = null;
        }

        $args = [
            'vendor' => $vendor,
            'module' => $module,
            'title' => $title,
            'description' => $description,
            'namespace' => $namespace,
            'assets' => $assets,
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

        $data = $this->template(__DIR__ . '/../../templates/Collector.php', $args);

        if (!file_put_contents($dir . '/src/Collector.php', $data)) {
            $output->writeln('<error>Unable to create Collector.php file</error>');
            return 1;
        }

        $data = $this->template(__DIR__ . '/../../templates/Installer.php', $args);

        if (!file_put_contents($dir . '/src/Installer.php', $data)) {
            $output->writeln('<error>Unable to create Installer.php file</error>');
            return 1;
        }

        if ($assets !== null) {
            if (!@mkdir($dir . '/' . $assets, 0775)) {
                $output->writeln('<error>Could not create assets directory</error>');
                return 1;
            }

            $data = $this->template(__DIR__ . '/../../templates/package.json.php', $args);

            if (!file_put_contents($dir . '/' . $assets . '/package.json', $data)) {
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
        $file = realpath(info()->rootDir() . '/' . trim($composer['repositories']['local'], '*/'));

        return $file ? $file : null;
    }

    private function createAssets(string $vendor, $module, string $dir, ?string $assets): bool
    {
        if ($assets === null) {
            return true;
        }
    }

    /**
     * @param $file
     * @param array $args
     * @return string
     */
    private function template($file, array $args): string
    {
        $__file_____ = $file;
        $__args_____ = $args;

        unset($file, $args);

        if (!ob_start()) {
            return '';
        }

        extract($__args_____, EXTR_SKIP);
        include $__file_____;

        return ob_get_clean();
    }
}