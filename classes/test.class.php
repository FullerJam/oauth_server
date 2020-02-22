<?php

class Test extends Db {
    public function getUsers(){
        $sql = "SELECT * FROM users";
        $stmt = $this->db_connect()->query($sql);

        while($row = $stmt->fetch())/*fetch is declared in db.class.php*/{
            echo $row['user'];
        }
    }
}