<?php

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use OAuth2ServerExamples\Entities\UserEntity;

class UserRepository implements UserRepositoryInterface
{
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }
    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username,$password,$grantType,ClientEntityInterface $clientEntity) {
        $sql = "SELECT password, id FROM users WHERE username=? AND approved_grant_types=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username,$grantType]);

        if ($row && $row["password" == $password]) {
            return new UserEntity($row["id"]);//pass id for get identifier
        } else {
            null;
        }

        return;
    }
}