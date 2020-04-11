<?php
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use OAuth2ServerExamples\Entities\ScopeEntity;

abstract class ScopeRepository implements ScopeRepositoryInterface
{
    protected $conn;

    public function __construct($conn){
        $this->conn = $conn;
    }

    public function returnAllScopes(){
        $sql = "SELECT * FROM scopes";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // /**
    //  * {@inheritdoc}
    //  */
    // public function getScopeEntityByIdentifier($scopeIdentifier)
    // {
    //     $scopes = [
    //         'basic' => [
    //             'description' => 'Basic details about you',
    //         ],
    //         'email' => [
    //             'description' => 'Your email address',
    //         ],
    //     ];

    //     if (\array_key_exists($scopeIdentifier, $scopes) === false) {
    //         return;
    //     }

    //     $scope = new ScopeEntity();
    //     $scope->setIdentifier($scopeIdentifier);

    //     return $scope;
    // }

    // /**
    //  * {@inheritdoc}
    //  */
    // public function finalizeScopes(
    //     array $scopes,
    //     $grantType,
    //     ClientEntityInterface $clientEntity,
    //     $userIdentifier = null
    // ) {
    //     // Example of programatically modifying the final scope of the access token
    //     if ((int) $userIdentifier === 1) {
    //         $scope = new ScopeEntity();
    //         $scope->setIdentifier('email');
    //         $scopes[] = $scope;
    //     }

    //     return $scopes;
    // }
}