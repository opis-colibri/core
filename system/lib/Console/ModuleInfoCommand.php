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

namespace Opis\Colibri\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;

use Opis\Colibri\App;
use Opis\Colibri\Module;


class ModuleInfoCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Displays informations about a module')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('p', new OutputFormatterStyle('yellow', null, array('bold')));
        $output->getFormatter()->setStyle('i', new OutputFormatterStyle('green', null, array('bold')));
        $output->getFormatter()->setStyle('e', new OutputFormatterStyle('white', null, array('bold')));
        $output->getFormatter()->setStyle('r', new OutputFormatterStyle('red', null, array('bold')));
        $output->getFormatter()->setStyle('b', new OutputFormatterStyle('blue', null, array('bold')));
        $module = $input->getArgument('module');
        
        if(!Module::exists($module))
        {
            $output->writeln('<error>Module <b-error>' . $module . '</b-error> doesn\'t exist.</error>');
            exit;
        }
        
        $info = Module::info($module);

        $output->writeln('<p>Name:</p> <i>'.$info['name'].'</i>');
        $output->writeln('<p>Title:</p> <i>'.$info['title'].'</i>');
        
        if($info['description'] !== '')
        {
            $output->writeln('<p>Description:</p> <i>'.$info['description'].'</i>');
        }
        else
        {
            $output->writeln('<p>Description:</p> <e>No description provided</e>');
        }

        $output->writeln('<p>Core version:</p> <i>'.$info['core'].'</i>');
        $output->writeln('<p>Namespace:</p> <i>'.$info['namespace'].'</i>');
        
        if(!empty($info['dependencies']))
        {
            $out = array();
            
            foreach($info['dependencies'] as $dependency)
            {
                if(Module::exists($dependency))
                {
                    if(Module::isInstalled($dependency))
                    {
                        if(Module::isEnabled($dependency))
                        {
                            $out[] = $dependency. '(<b>enabled</b>)';
                        }
                        else
                        {
                            $out[] = $dependency. '(<p>disabled</p>)';
                        }
                    }
                    else
                    {
                        $out[] = $dependency . '(<e>uninstalled</e>)';
                    }
                }
                else
                {
                    $out[] = $dependency . '(<r>missing</r>)';
                }
            }
            
            $output->writeln('<p>Dependencies:</p> <i>'. implode(', ', $out).'</i>');
        }
        else
        {
            $output->writeln('<p>Dependencies:</p> <e>No dependencies</e>');
        }
        
        $output->writeln('<p>Hidden</p>: <i>'.($info['hidden'] ? 'TRUE' : 'FALSE').'</i>');
        $output->writeln('<p>Directory</p>: <i>'.$info['directory'].'</i>');
        $output->writeln('<p>Source</p>: <i>'.str_replace($info['directory'], '{Directory}', $info['source']).'</i>');
        $output->writeln('<p>Collector</p>: <i>'.str_replace($info['directory'], '{Directory}', $info['collector']).'</i>');
        
        if($info['assets'])
        {
            $output->writeln('<p>Assets:</p> <i>'.str_replace($info['directory'], '{Directory}', $info['assets']).'</i>');
        }
        else
        {
            $output->writeln('<p>Assets:</p> <e>No assets folder</e>');
        }
    }
    
}
