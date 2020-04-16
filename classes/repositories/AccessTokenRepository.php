<?php

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{

    protected $db;

    public function __construct($db){
        $this->db = $db;
    }
    /**
     * {@inheritdoc}
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        // Some logic here to save the access token to a database
        $suppliedScopes = implode(" ", $accessTokenEntity->getScopes()); //implode the scopes array so it can be saved to a database
        $sql = "INSERT INTO access_tokens(access_token, token_expires, user_id, scope, client_id) VALUES (?,?,?,?,?)";
        $stmt = $this->db->prepare($sql); 
        $stmt->execute([$accessToken->getIdentifier(), $accessToken->getExpiryDateTime()->getTimestamp(), $accessToken->getUserIdentifier(), $scopes, $accessToken->getClient()->getIdentifier()]);
    }

    /**
     * {@inheritdoc}
     */
    public function revokeAccessToken($tokenId)
    {
        $sql = "UPDATE access_tokens SET is_revoked=true WHERE access_token=?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tokenId]);
    }

    /**
     * {@inheritdoc}
     */
    public function isAccessTokenRevoked($tokenId)
    {
        $sql = "SELECT revoked FROM access_tokens WHERE access_token=$tokenId";
        $stmt = $this->db->prepare($sql);
        $row = $stmt->fetch();
        return $row["is_revoked"];
    }

    /**
     * {@inheritdoc}
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }
}