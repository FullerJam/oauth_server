<?php

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use OAuth2ServerExamples\Entities\UserEntity;

class UserRepository implements UserRepositoryInterface
{
    protected $db;

    public function __construct($db){
        $this->db = $db;
    }
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username,$password,$grantType,ClientEntityInterface $clientEntity) {
        $sql = "SELECT password, id FROM users WHERE username=? AND approved_grant_types=?";
        $stmt = $this->db->prepare($sql);
        $row = $stmt->execute([$username,$grantType]);

        if ($row && password_verify($password, $row["password"])) {
            return new UserEntity($row["id"]);//pass id for getIdentifier() in UEI
        } 
        
        return null;
        
        //https://www.php.net/manual/en/pdostatement.bindparam.php  use like operator as grant types may not always match exactly?
        
    }
}