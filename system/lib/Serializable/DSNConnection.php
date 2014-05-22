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

namespace Opis\Colibri\Serializable;

use Closure;
use Serializable;
use Opis\Database\Connection;

class DSNConnection implements Serializable
{
    
    protected $connection;
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    protected function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $connection;
    }
    
    public function generic($dsn, $username = null, $password = null)
    {
        return $this->setConnection(new GenericConnection($dsn, $username, $password));
    }
    
    public function firebird($username, $password)
    {
        return $this->setConnection(Connection::firebird($username, $password));
    }
    
    public function ibm($username, $password)
    {
        return $this->setConnection(Connection::ibm($username, $password));
    }
    
    public function oracle($username, $password)
    {
        return $this->setConnection(Connection::oracle($username, $password));
    }
    
    public function mysql($username, $password)
    {
        return $this->setConnection(Connection::mysql($username, $password));
    }
    
    public function mariaDB($username, $password)
    {
        return $this->setConnection(Connection::mariaDB($username, $password));
    }
    
    public function postgreSQL($username, $password)
    {
        return $this->setConnection(Connection::postgreSQL($username, $password));
    }
    
    public function sqlServer($username, $password)
    {
        return $this->setConnection(Connection::sqlServer($username, $password));
    }
    
    public function mssql($username, $password, $driver = 'dblib')
    {
        return $this->setConnection(Connection::mssql($username, $password));
    }
    
    public function sqlite($path = null)
    {
        return $this->setConnection(Connection::sqlite($username, $password));
    }
    
    public function sqlite2($path = null)
    {
        return $this->setConnection(Connection::sqlite2($username, $password));
    }
    
    public function serialize()
    {
        return serialize($this->connection);
    }
    
    public function unserialize($data)
    {
        $this->connection =  unserialize($data);
    }
    
}
