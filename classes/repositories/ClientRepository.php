<?php


use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
// use League\OAuth2\Server\Entities\ClientEntity; //commented out, preventing error where slim couldnt find ClientEntity.. CRI uses use League\OAuth2\Server\Entities\ClientEntityInterface; anyway

class ClientRepository implements ClientRepositoryInterface
{
    // const CLIENT_NAME = 'My Awesome App';
    // const REDIRECT_URI = 'http://foo/bar';

    protected $db;

    public function __construct($db){
        $this->db = $db;
    }


    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier)
    {
        $client = new ClientEntity();
        $sql = "SELECT name, redirect_uri FROM clients WHERE client_id=?";
        $stmt = $this->db->prepare($sql);
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
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientIdentifier, $grantType]);
        $row = $stmt->fetch();
        if ($row && $clientSecret == $row["client_secret"]){
            return true;
        } else {
            return false;
        }
      //https://www.php.net/manual/en/pdostatement.bindparam.php  use like operator as grant types may not always match exactly?


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