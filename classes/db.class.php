<?php

class Db {
    /*not working for some reason 
    Notice: Undefined variable: host in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined property: Test::$ in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined variable: dbName in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined property: Test::$ in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 13

Notice: Undefined variable: user in C:\xampp\htdocs\oauth_server\classes\db.class.php on line 15
    */
    // private $host = "localhost"; 
    // private $user = "root";
    // private $pwd = "";
    // private $dbName = "oauth2.0";

    public function db_connect(){

        //declared locally for now until i can consult tutor
        $host = "localhost"; 
        $user = "root";
        $pwd = "";
        $dbName = "oauth2.0";
        
        try {
            // data source name
            $dsn = 'mysql:host='. $host .';dbname='. $dbName;
            //PHP Data Objects
            $pdo = new PDO($dsn, $user, $pwd);

            // connect with default attribute for how to pull out data. Associative array as default
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            return $pdo;
            
        } catch (PDOException $error) {
            echo "Error: ".$error->getMessage();
        }

    }
}

?>