<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

namespace Colibri\Module\Install;

class Check
{
    
    protected static $errors = false;
    
    protected function __construct()
    {
        
    }
    
    protected static function message($type, $check, $attr, $message)
    {
        return array(
            'type' => $type,
            'check' => $check,
            'attribute' => $attr,
            'message' => $message,
        );
    }
    
    protected static function success($check, $attr, $message)
    {
        return static::message('success', $check, $attr, $message);
    }
    
    protected static function error($check, $attr, $message)
    {
        static::$errors = true;
        return static::message('error', $check, $attr, $message);
    }
    
    
    public static function hasErrors()
    {
        return static::$errors;
    }
    
    public static function pdo()
    {
        if(!class_exists('\PDO'))
        {
            return static::error('Database', 'PDO extension', 'Databases are not supported.');
        }
        
        return static::success('Database', 'PDO extension', 'Databases are supported.');
    }
    
    public static function assets()
    {
        if(!is_writable(COLIBRI_PUBLIC_ASSETS_PATH))
        {
            return static::error('/public/assets', COLIBRI_PUBLIC_ASSETS_PATH, 'This folder must be writable.');
        }
        
        $path = COLIBRI_PUBLIC_ASSETS_PATH . '/module';
        
        if(file_exists($path))
        {
            if(!is_dir($path))
            {
                return static::error('/public/assets/module', $path, 'Not a directory.');
            }
            elseif(!is_writable($path))
            {
                return static::error('/public/assets/module', $path, 'This folder must be writable.');
            }
        }
        else
        {
            mkdir($path);
        }
        
        return static::success('/public/assets', COLIBRI_PUBLIC_ASSETS_PATH, 'The folder is writable.');
    }
    
    public static function storages()
    {
        if(!is_writable(COLIBRI_STORAGES_PATH))
        {
            return array(static::error('/storage', COLIBRI_STORAGES_PATH, 'This folder must be writable.'));
        }
        
        $result[] = static::success('/storage', COLIBRI_STORAGES_PATH, 'The folder is writable.');
        
        foreach(array('cache', 'session', 'config', 'templates', 'uploads') as $folder)
        {
            $path = COLIBRI_STORAGES_PATH . '/' . $folder;
            
            if(file_exists($path))
            {
                if(is_file($path))
                {
                    $result[] = static::error('/storage/' . $folder, $path, 'Not a directory.');
                }
                elseif(!is_writable($path))
                {
                    $result[] = static::error('/storage/' . $folder, $path, 'The folder is not writable.');
                }
            }
            else
            {
                mkdir($path);
            }
        }
        
        
        return $result;
    }
    
}
