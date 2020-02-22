<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
// Include all the Slim dependencies. Composer creates an 'autoload.php' inside
// the 'vendor' directory which will, in turn, include all required dependencies.
require '../vendor/autoload.php';
require_once '../classes/repositories/ClientRepository.php';
require_once '../classes/repositories/AccessTokenRepository.php';
require_once '../classes/repositories/ScopeRepository.php';
require_once '../classes/repositories/AuthCodeRepository.php';
require_once '../classes/repositories/RefreshTokenRepository.php';
require_once '../classes/repositories/ClientEntity.php';
require_once '../classes/repositories/UserEntity.php';
require_once '../classes/repositories/AccessTokenEntity.php';
require_once '../classes/repositories/ScopeEntity.php';
require_once '../classes/repositories/AuthCodeEntity.php';
require_once '../classes/repositories/RefreshTokenEntity.php';



// Create a new Slim App object. (v3 method)
$app = new \Slim\App;

$container = $app->getContainer();

$container['db'] = function() {
    $conn = new PDO("mysql:host=localhost;dbname=oauth2.0", "root", "");
    return $conn;
};

$container['authServer'] = function($container){
    $db = $container->get('db');

    // Init our repositories
    $clientRepository = new ClientRepository($db); // instance of ClientRepositoryInterface
    $scopeRepository = new ScopeRepository($db); // instance of ScopeRepositoryInterface
    $accessTokenRepository = new AccessTokenRepository($db); // instance of AccessTokenRepositoryInterface
    $authCodeRepository = new AuthCodeRepository($db); // instance of AuthCodeRepositoryInterface
    $refreshTokenRepository = new RefreshTokenRepository($db); // instance of RefreshTokenRepositoryInterface
    
    $privateKey = '../private.key';
    //$privateKey = new CryptKey('file://path/to/private.key', 'passphrase'); // if private key has a pass phrase
    $encryptionKey = 'n8Joj0/sNX1PgY3XlOrIY+D0B+bZKcuo7ofGaans82k='; // generate 'openssl rand -base64 32' in console omit the 's
    // Setup the authorization server
    $server = new \League\OAuth2\Server\AuthorizationServer(
        $clientRepository,
        $accessTokenRepository,
        $scopeRepository,
        $privateKey,
        $encryptionKey
    );

    $grant = new \League\OAuth2\Server\Grant\AuthCodeGrant(
         $authCodeRepository,
         $refreshTokenRepository,
         new \DateInterval('PT10M') // authorization codes will expire after 10 minutes
     );
     
    $grant->setRefreshTokenTTL(new \DateInterval('P1M')); // refresh tokens will expire after 1 month

    // Enable the authentication code grant on the server
    $server->enableGrantType(
    $grant,
    new \DateInterval('PT1H') // access tokens will expire after 1 hour
    );

    return $server;
};



//client requests an access token
$app->post('/access_token', function (ServerRequestInterface $request, ResponseInterface $response) use ($server) {

    try {
        
        // Try to respond to the request
        return $server->respondToAccessTokenRequest($request, $response);

    } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
    
        // All instances of OAuthServerException can be formatted into a HTTP response
        return $exception->generateHttpResponse($response);
        
    } catch (\Exception $exception) {
        
        // Unknown exception
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($exception->getMessage());
        return $response->withStatus(500)->withBody($body);
    }
});


//client redirects the user to an authorization endpoint
$app->get('/authorize', function (ServerRequestInterface $request, ResponseInterface $response) use ($server) {
   
    try {
    
        // Validate the HTTP request and return an AuthorizationRequest object.
        $authRequest = $server->validateAuthorizationRequest($request);
        
        // The auth request object can be serialized and saved into a user's session.
        // You will probably want to redirect the user at this point to a login endpoint.
        
        // Once the user has logged in set the user on the AuthorizationRequest
        $authRequest->setUser(new UserEntity()); // an instance of UserEntityInterface
        
        // At this point you should redirect the user to an authorization page.
        // This form will ask the user to approve the client and the scopes requested.
        
        // Once the user has approved or denied the client update the status
        // (true = approved, false = denied)
        $authRequest->setAuthorizationApproved(true);
        
        // Return the HTTP redirect response
        return $server->completeAuthorizationRequest($authRequest, $response);
        
    } catch (OAuthServerException $exception) {
    
        // All instances of OAuthServerException can be formatted into a HTTP response
        return $exception->generateHttpResponse($response);
        
    } catch (\Exception $exception) {
    
        // Unknown exception
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($exception->getMessage());
        return $response->withStatus(500)->withBody($body);
        
    }
});






// Run the application
$app->run();