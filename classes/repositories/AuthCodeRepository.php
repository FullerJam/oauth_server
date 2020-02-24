<?php

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use OAuth2ServerExamples\Entities\AuthCodeEntity;

class AuthCodeRepository extends Db implements AuthCodeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        // Some logic to persist the auth code to a database
        $suppliedScopes = implode(" ", $authCodeEntity->getScopes());
        $sql = "INSERT INTO authorisation_codes(authorisation_code, code_expires, user_id, scope, client_id) VALUES (?,?,?,?,?)";
        $stmt = $this->db_connect()->prepare($sql);   
        $stmt->execute([$authCodeEntity->getIdentifier(), $authCodeEntity->getExpiryDateTime()->getTimestamp(), $authCodeEntity->getUserIdentifier(), $suppliedScopes, $authCodeEntity->getClient()->getIdentifier()])    
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAuthCode($codeId)
    {
        // Some logic to revoke the auth code in a database
        $stmt = $this->db_connect()->prepare("UPDATE authorisation_codes SET revoked=true WHERE authorisation_code=?");
        $stmt->execute([$codeId]);

    }

    /**
     * {@inheritdoc}
     */
    public function isAuthCodeRevoked($codeId)
    {
        $sql = "SELECT revoked FROM oauth_auth_codes WHERE auth_code=$codeId";
        $stmt = $this->db_connect()->query($sql);
        $result = $stmt->fetch();
        return $result; 
        // The auth code has not been revoked
    }

    /**
     * {@inheritdoc}
     */
    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }
}