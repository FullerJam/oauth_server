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

//include database
require_once '../classes/db.class.php';

// Create a new Slim App object. (v3 method)
$app = new \Slim\App;

$container = $app->getContainer();

$conn = new PDO("mysql:host=localhost;dbname=oauth2.0", "root", "");

$container['db'] = function() use($conn) {
    return $conn;
};

// Init our repositories
$clientRepository = new ClientRepository($conn); // instance of ClientRepositoryInterface
$scopeRepository = new ScopeRepository(); // instance of ScopeRepositoryInterface
$accessTokenRepository = new AccessTokenRepository(); // instance of AccessTokenRepositoryInterface
$authCodeRepository = new AuthCodeRepository(); // instance of AuthCodeRepositoryInterface
$refreshTokenRepository = new RefreshTokenRepository(); // instance of RefreshTokenRepositoryInterface

$privateKey = '../private.key'; //private key file, added to git ignore

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

// Enable the client credentials grant on the server
$server->enableGrantType(
    new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
    new \DateInterval('PT1H') // access tokens will expire after 1 hour
);


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
$app->get('/authorise', function (ServerRequestInterface $request, ResponseInterface $response) use ($server) {
   
    try {
        //getQuery() returns query as string - getQueryParams() returns associative array of those params -> http://www.slimframework.com/docs/v3/objects/request.html
        $queryParams = $req->getQueryParams(); //flow part one params [response_type, client_id, redirect_uri, scope, state] -> https://oauth2.thephpleague.com/authorization-server/auth-code-grant/
        // $queryParams["response_type"]

        // Validate the HTTP request and return an AuthorizationRequest object.
        $authRequest = $server->validateAuthorizationRequest($request);
        // found here - vendor\league\oauth2-server\src\AuthorizationServer.php
        //dont think i have to do anything
        
        // The auth request object can be serialized and saved into a user's session.
        // You will probably want to redirect the user at this point to a login endpoint.
        if(!isset($_SESSION['authorised_user'])){
            //login -- 
            echo "
            <!DOCTYPE html>
            <html lang='en'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Oauth Login</title>
            </head>
            <body>
                <h1>Welcome to the authorisation server login</h1>
                <form action='localhost/oauth_server/authorisation_server/login' method='post'>
                   <div class='form-wrapper' style='max-width:320px;margin:0 auto;'>
                        <label for='usr'>Username</label>
                        <input type='text' placeholder='Enter Username' name='usr' required>
                        <label for='psd'>Password</label>
                        <input type='password' placeholder='Enter Password' name='pwd' required>
                        <input type='hidden' id='' name='' value='".$queryParams["response_type"]."'>
                        <input type='hidden' id='' name='' value='".$queryParams["client_id"]."'>
                        <input type='hidden' id='' name='' value='".$queryParams["redirect_uri"]."'>
                        <input type='hidden' id='' name='' value='".$queryParams["scope"]."'>
                        <input type='hidden' id='' name='' value='".$queryParams["state"]."'>
                        <button type='submit'>Login</button>
                    </div>
                </form>
            </body>
            </html>
            ";
        } else {
              
        // Once the user has logged in set the user on the AuthorizationRequest
        $authRequest->setUser(new UserEntity()); // an instance of UserEntityInterface
        
        // At this point you should redirect the user to an authorization page.
        // This form will ask the user to approve the client and the scopes requested.
        
        // Once the user has approved or denied the client update the status
        // (true = approved, false = denied)
        $authRequest->setAuthorizationApproved(true);
        
        // Return the HTTP redirect response
        return $server->completeAuthorizationRequest($authRequest, $response);
        
        } 
    }
    catch (OAuthServerException $exception) {p
    
        // All instances of OAuthServerException can be formatted into a HTTP response
        return $exception->generateHttpResponse($response);
        
    } catch (\Exception $exception) {
    
        // Unknown exception
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($exception->getMessage());
        return $response->withStatus(500)->withBody($body);
        
    }
});

$app->post('/login', function (ServerRequestInterface $request, ResponseInterface $response) use ($server) {

    try{
        session_start();
        $postData = $req->getParsedBody();
        $usr = $postData["usr"];
        $pwd = $postData["pwd"];
        $db = new Db();
        $sql = "SELECT * from users WHERE user=?";
        $results = $this->db_connect()->prepare($sql);
        $results->execute([$usr]);
        $row = $results->fetch();

        if($row != false){
            if ($pw == $row["password"]){
                $_SESSION["authorised_user"] = $usr;
                header("Location:localhost/oauth_server/authorisation_server/authorise");
            } else {
                throw new Exception("No user registered by those credentials")
            }
        }
    } 
    catch (Exception $e) {
    echo $e->getMessage();
    header("refresh:5;url=localhost/oauth_server/authorisation_server/authorise");
    }

});





// Run the application
$app->run();
