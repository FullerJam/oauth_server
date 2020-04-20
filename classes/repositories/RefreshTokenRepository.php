<?php

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    protected $db;

    public function __construct($db){
        $this->db = $db;
    }
    /**
     * {@inheritdoc}
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        //scopes,clientId&userId not in RefreshTokenEntityInterface so removing from db columns, adding access token to match get methods
        // Some logic to persist the refresh token in a database
        $sql = "INSERT INTO refresh_tokens(refresh_token, token_expires, access_tokens) VALUES (?,?,?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$refreshTokenEntity->getIdentifier(),$refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s'),$refreshTokenEntity->getAccessToken()]);
    } // Recoverable fatal error: Object of class DateTimeImmutable could not be converted to string, https://www.php.net/manual/en/datetime.gettimestamp.php timestamp returns 0000-00-00 00:00:00 expiry time, tried https://www.php.net/manual/en/datetime.format.php. $refreshTokenEntity->getExpiryDateTime()->format('Y-m-d H:i:s') returned 0000-00-00 00:00:00 as well in token_expires

    /**
     * {@inheritdoc}
     */
    public function revokeRefreshToken($tokenId)
    {
        // Some logic to revoke the refresh token in a database, added is_revoked to refresh_tokens in db
        $sql = "UPDATE refresh_tokens SET is_revoked=true WHERE refresh_token=?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tokenId]);

    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        $sql = "SELECT is_revoked FROM refresh_tokens WHERE refresh_token=$tokenId";
        $stmt = $this->db->prepare($sql);
        $row = $stmt->fetch();
        return $row["is_revoked"]; // revoked set to false as default
    }

    /**
     * {@inheritdoc}
     */
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }
}