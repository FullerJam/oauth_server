<?php

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
// use OAuth2ServerExamples\Entities\AuthCodeEntity;


class AuthCodeRepository implements AuthCodeRepositoryInterface
{

    protected $db;

    public function __construct($db){
        $this->db = $db;
    }
    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        // Some logic to persist the auth code to a database
        $suppliedScopes = implode(" ", $authCodeEntity->getScopes()); //implode the scopes array so it can be saved to a database
        
        $sql = "INSERT INTO authorisation_codes(authorisation_code, code_expires, user_id, scope, client_id) VALUES (?,?,?,?,?)"; //SQL statement

        $stmt = $this->db->prepare($sql); //prepared statement, use TokenInterface extend from AuthCodeEntityInterface. Token interface functions to retrieve query string data    

        $stmt->execute([$authCodeEntity->getIdentifier(), $authCodeEntity->getExpiryDateTime()->getTimestamp(), $authCodeEntity->getUserIdentifier(), $suppliedScopes, $authCodeEntity->getClient()->getIdentifier()]);    
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode($codeId)
    {
        // Some logic to revoke the auth code in a database
        $sql = "UPDATE authorisation_codes SET is_revoked=true WHERE authorisation_code=?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$codeId]);

    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId)
    {
        $sql = "SELECT revoked FROM authorisation_codes WHERE auth_code=$codeId";
        $stmt = $this->db->prepare($sql);
        $row = $stmt->fetch();
        return $row["is_revoked"];
    }

    /**
     * {@inheritdoc}
     */
    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }
}
