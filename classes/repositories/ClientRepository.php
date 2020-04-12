<?php

namespace OAuth2ServerExamples\Repositories;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use OAuth2ServerExamples\Entities\ClientEntity;

class ClientRepository implements ClientRepositoryInterface
{
    const CLIENT_NAME = 'My Awesome App';
    const REDIRECT_URI = 'http://foo/bar';

    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }


    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier)
    {
        $client = new ClientEntity();
        $sql = "SELECT name, redirect_uri FROM clients WHERE client_id=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientIdentifier]);
        $row = $stmt->fetch(); 
        $client->setIdentifier($clientIdentifier);
        $client->setName($row["name"]);
        $client->setRedirectUri($row["redirect_uri"]);
        $client->setConfidential();

        return $client;
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $sql = "SELECT client_secret FROM clients WHERE client_id=? AND grant_types=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientIdentifier, $grantType]);
        $row = $stmt->fetch();
        if ($row == true && $clientSecret == $row["client_secret"]){
            return true;
        } else {
            return false;
        }
      
        // Check if client is registered
    //     if (\array_key_exists($clientIdentifier, $clients) === false) {
    //         return false;
    //     }

    //     if (
    //         $clients[$clientIdentifier]['is_confidential'] === true
    //         && \password_verify($clientSecret, $clients[$clientIdentifier]['secret']) === false
    //     ) {
    //         return false;
    //     }

    //     return true;
    // }
    }

}