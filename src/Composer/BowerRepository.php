<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

namespace Opis\Colibri\Composer;

use Composer\Config;
use Composer\DependencyResolver\Pool;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\IOInterface;
use Composer\Repository\ComposerRepository;
use Composer\Semver\VersionParser;
use Composer\Util\Filesystem;
use Composer\Util\Git as GitUtil;
use Composer\Util\ProcessExecutor;
use Composer\Util\RemoteFilesystem;
use Opis\Colibri\Composer\Util\Git;
use Opis\Colibri\Composer\Util\GitRepo;

class BowerRepository extends ComposerRepository
{
    protected $fs;
    protected $git;
    protected $appInfo;
    protected $cached = [];
    protected $synced = [];
    protected $resolved = [];
    protected $bowerDir;
    protected $versionParser;


    public function __construct(array $repoConfig, IOInterface $io, Config $config, EventDispatcher $eventDispatcher = null, RemoteFilesystem $rfs = null)
    {
        $this->fs = new Filesystem();
        $this->versionParser = new VersionParser();
        $this->bowerDir = $config->get('cache-dir') . '/bower';
        $this->fs->ensureDirectoryExists($this->bowerDir . '/packages');
        $this->fs->ensureDirectoryExists($this->bowerDir . '/repositories');
        GitUtil::cleanEnv();
        $this->git = new GitUtil($io, $config, new ProcessExecutor($io), $this->fs);
        parent::__construct($repoConfig, $io, $config, $eventDispatcher, $rfs);
    }

    public function hasProviders()
    {
        return true;
    }

    public function whatProvides(Pool $pool, $name, $bypassFilters = false)
    {
        if(strpos($name, 'bower__components/') !== 0){
            return [];
        }

        $path = substr($name, strlen('bower__components/'));

        if(strpos($path, '--') === false){
            if(!isset($this->resolved[$path])){
                $this->resolved[$path] = $this->resolvePackage($path);
            }
            if(false === $json = $this->resolved[$path]){
                return [];
            }
            $url = $json['url'];
        } else {
            $url = 'https://github.com/' . str_replace('--', '/', $path) . '.git';
        }

        $hash = md5($url);

        if(isset($this->cached[$hash])){
            return $this->cached[$hash];
        }

        return $this->cached[$hash] = $this->getBowerPackages($name, $url, $hash);
    }

    protected function getBowerPackages($name, $url, $hash): array
    {
        $dir = $this->bowerDir . '/repositories/' . $hash;
        $targetFile = $this->bowerDir . '/packages/' . $hash . '.json';


        $load = true;
        $packages = [];

        if(file_exists($targetFile)){
            $content = json_decode(file_get_contents($targetFile), true);
            if($content && $content['ttl'] > time()){
                $load = false;
                $packages = $content['packages'];
            }
        }

        if($load){
            $this->syncMirror($url, $dir);
            $packages = $this->mapPackages(Git::open($dir), $url, $name, $dir);
            file_put_contents($targetFile, json_encode(['ttl' => time() + 24 * 3600, 'packages' => $packages]));
        }

        $packs = [];

        foreach ($packages as $version){
            $package = $this->createPackage($version, 'Composer\Package\CompletePackage');
            $package->setRepository($this);
            $packs[] = $package;
        }

        return $packs;
    }


    protected function mapPackages(GitRepo $repo, $url, $name, $dir)
    {
        $packs = [];

        foreach ($repo->list_tags() as $tag){
            try{
                $this->versionParser->normalize($tag);
            }catch (\Exception $e){
                continue;
            }

            if(!($json = $this->readBowerFile($repo, $tag))){
                continue;
            }

            $reference = trim($repo->run("rev-list -n 1 $tag"));

            $package = [
                'name' => $name,
                'version' => $tag,
                'type' => 'component',
                'source' => [
                    'type' => 'git',
                    'url' => $url,
                    'reference' => $reference
                ],
                'extra' => [
                    'component' => [
                        'name' => $json['name'],
                        'files' => ['**']
                    ]
                ]
            ];

            if(preg_match('`^http(s)?\://github.com/([a-zA-Z0-9\-\_\.]+/[a-zA-Z0-9\-\_\.]+)\.git$`', $url, $match)){
                $package['dist'] = [
                    'type' => 'zip',
                    'url' => 'https://api.github.com/repos/' . $match[2] .'/zipball/' . $reference,
                    'reference' => $reference,
                    'shasum' => ''
                ];
            }

            if(!isset($json['dependencies'])){
                $packs[] = $package;
                continue;
            }

            $require = [];

            foreach ($json['dependencies'] as $depname => $dependency){
                if(null !== $dep = $this->extractDependency($depname, $dependency)){
                    $require[$dep['name']] = $dep['version'];
                }
            }

            if(!empty($require)){
                $package['require'] = $require;
            }

            $packs[] = $package;
        }

        return $packs;
    }

    protected function extractDependency($name, $dependency)
    {
        $version = '*';

        if(false !== $sep = strpos($dependency, '#')){
            $name = substr($dependency, 0, $sep);
            $version = substr($dependency, $sep + 1);
            if($version === 'latest'){
                $version = '*';
            }
        }

        if(strpos($name, 'https://github.com/') === 0){
            $name = substr($name, strlen('http://github.com/'));
        }

        $name = str_replace('/', '--', strtolower($name));

        if(!preg_match('/[a-z0-9\-_]+/', $name)){
            return null;
        }

        try{
            $this->versionParser->parseConstraints($version);
        }catch (\Exception $e){
            $version = 'dev-' . $version;
        }

        return [
            'name' => 'bower__components/' . $name,
            'version' => $version,
        ];
    }

    protected function readBowerFile(GitRepo $repo, $tag)
    {
        try{
            $content = $repo->run("show $tag:bower.json");
            return json_decode($content, true);
        }catch (\Exception $e){
            return false;
        }
    }

    protected function resolvePackage($name)
    {
        $packageFile = $this->bowerDir . '/registry.json';

        if(file_exists($packageFile)){
            $packages = json_decode(file_get_contents($packageFile), true);
        } else {
            $packages = [];
        }

        if(!isset($packages[$name])){
            if(null !== $json = json_decode(file_get_contents('http://bower.herokuapp.com/packages/' . $name), true)){
                $packages[$name] = [
                    'time' => time(),
                    'data' => $json,
                ];
                file_put_contents($packageFile, json_encode($packages));
            }
        }

        return $packages[$name]['data'] ?? false;
    }

    protected function syncMirror($url, $dir)
    {
        (function() use($dir, $url) {
            // update the repo if it is a valid git repository
            if (is_dir($dir) && 0 === $this->process->execute('git rev-parse --git-dir', $output, $dir) && trim($output) === '.') {
                try {
                    $commandCallable = function ($url) {
                        return sprintf('git remote set-url origin %s && git remote update --prune origin', ProcessExecutor::escape($url));
                    };
                    $this->io->write('<info>Bower: </info> Updating ' . $url);
                    $this->runCommand($commandCallable, $url, $dir);
                } catch (\Exception $e) {
                    return false;
                }

                $repo = Git::open($dir);
                return true;
            }

            // clean up directory and do a fresh clone into it
            $this->filesystem->removeDirectory($dir);

            $commandCallable = function ($url) use ($dir) {
                return sprintf('git clone --mirror %s %s', ProcessExecutor::escape($url), ProcessExecutor::escape($dir));
            };

            $this->io->write('<info>Bower: </info> Cloning ' . $url);
            $this->runCommand($commandCallable, $url, $dir, true);

            return true;
        })->call($this->git);
    }
}