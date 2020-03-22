<?php

use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class UserEntity implements UserEntityInterface
{
    
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }
    /**
     * Return the user's identifier.
     *
     * @return mixed
     */
    public function getIdentifier($id)
    {
        return $id;
    }
}