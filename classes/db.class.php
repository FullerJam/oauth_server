<?php
class Database{
    private $host ="localhost";
    private $user ="root";
    private $pwd ="";
    private $dbName ="oauth2.0";

    protected function connect(){
        // data source name
        $dsn = 'mysql:host='.$this->$host.';dbname='.$this->$dbName;

        $pdo = new PDO($dsn,$this->$user, $this->pwd);

        // connect with default attribute for how to pull out data. Associative array as default
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }
}