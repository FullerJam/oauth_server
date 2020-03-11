<?php

//include('defines.php');

class Db {
    /*not working for some reason 
    Notice: Undefined variable: host in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined property: Test::$ in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined variable: dbName in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined property: Test::$ in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined variable: user in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 15
    */
     private $host = "localhost"; 
    private $user = "root";
    private $pwd = "";
   private $dbName = "oauth2.0";

    public function __construct($host, $user, $pwd, $dbName) {
        $this->host = $host;
        $this->user = $user;
        $this->pwd = "";
        $this->dbName = $dbName;
    }

    public function db_connect(){

       
       
        try {
            // data source name
            $dsn = 'mysql:host='. $this->host .';dbname='. $this->dbName;
            //PHP Data Objects
            $pdo = new PDO($dsn, $this->user, $this->pwd);

            // connect with default attribute for how to pull out data. Associative array as default
            // $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return $pdo;
            
        } catch (PDOException $error) {
            echo "Error: ".$error->getMessage();
        }

    }
}

$db = new Db("localhost", "root", "", "oauth2.0");
$db->db_connect();
?>
