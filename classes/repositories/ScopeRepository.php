<?php
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

// use OAuth2ServerExamples\Entities\ScopeEntity;

class ScopeRepository implements ScopeRepositoryInterface
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function returnAllScopes()
    {
        $sql = "SELECT * FROM scopes";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // returns an array indexed by column name https://www.php.net/manual/en/pdostatement.fetch.php
        //fetchAll() returns an array containing all of the remaining rows in the result set.
        //https://www.php.net/manual/en/pdostatement.fetchall.php
    }

    /**
     * {@inheritdoc}
     */
    public function getScopeEntityByIdentifier($scopeIdentifier)
    {

        $sql = "SELECT id, description FROM scopes WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$scopeIdentifier]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $scopeArray = [$result["id"] => $result["description"]]; // array_key_exists expects associative array
        if (\array_key_exists($scopeIdentifier, $scopeArray) === false) {
            return;
        }
        $scope = new ScopeEntity($scopeIdentifier, $result["description"]);
        $scope->setIdentifier($scopeIdentifier);

        return $scope;

    }

    /**
     * {@inheritdoc}
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ) {
        // Example of programatically modifying the final scope of the access token
        if ((int) $userIdentifier === 1) {
            $scope = new ScopeEntity();
            $scope->setIdentifier('email');
            $scopes[] = $scope;
        }

        return $scopes;
    }
}
