<?php

use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class UserEntity implements UserEntityInterface
{
    use EntityTrait;

    protected $conn;
    public $id;

    public function __construct($id, $conn){
        $this->conn = $conn;
        $this->id = $id;
    }
    /**
     * Return the user's identifier.
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->id;
    }
}