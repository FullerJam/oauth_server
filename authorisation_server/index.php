<?php
use Psr\Http\Message\ServerRequestInterface as Request;
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
// php renderer object for phtml
$view = new \Slim\Views\PhpRenderer('views');

$container = $app->getContainer();

$conn = new PDO("mysql:host=localhost;dbname=oauth2.0", "root", "");

$container['db'] = function () use ($conn) {
    return $conn;
};

// Init our repositories
$clientRepository = new ClientRepository($conn); // instance of ClientRepositoryInterface
$scopeRepository = new ScopeRepository($conn); // instance of ScopeRepositoryInterface
$accessTokenRepository = new AccessTokenRepository($conn); // instance of AccessTokenRepositoryInterface
$authCodeRepository = new AuthCodeRepository($conn); // instance of AuthCodeRepositoryInterface
$refreshTokenRepository = new RefreshTokenRepository($conn); // instance of RefreshTokenRepositoryInterface

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
$app->post('/access_token', function(Request $req, Response $res, array $args) {

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
})->setName('accessToken');

//client redirects the user to an authorization endpoint
$app->get('/authorise', function(Request $req, Response $res, array $args) {

    try {
        //getQuery() returns query as string - getQueryParams() returns associative array of those params -> http://www.slimframework.com/docs/v3/objects/request.html
        $queryParams = $req->getQueryParams(); //flow part one params [response_type, client_id, redirect_uri, scope, state] -> https://oauth2.thephpleague.com/authorization-server/auth-code-grant/
        // $queryParams["response_type"]
        //save all params to session variables
        session_start();
        $_SESSION["res_type"] = $queryParams["response_type"];
        $_SESSION["client_id"] = $queryParams["client_id"];
        $_SESSION["redirect_uri"] = $queryParams["redirect_uri"];
        $_SESSION["scope"] = $queryParams["scope"];
        $_SESSION["state"] = $queryParams["state"];

        // Validate the HTTP request and return an AuthorizationRequest object.
        $authRequest = $server->validateAuthorizationRequest($request);
        // found here - vendor\league\oauth2-server\src\AuthorizationServer.php
        //dont think i have to do anything

        // The auth request object can be serialized and saved into a user's session.
        // You will probably want to redirect the user at this point to a login endpoint.

        if (!isset($_SESSION['authorised_user'])) {
            //login --
            $res = $view->render($res, 'login.phtml');
            return $res;
        } else {
            

            // Once the user has logged in set the user on the AuthorizationRequest
            $authRequest->setUser(new UserEntity($_SESSION['authorised_user'])); // an instance of UserEntityInterface

            // At this point you should redirect the user to an authorization page.
            // This form will ask the user to approve the client and the scopes requested.

            
            unset($_SESSION['authorised_user']);
            // Once the user has approved or denied the client update the status
            // (true = approved, false = denied)
            $authRequest->setAuthorizationApproved(true);
            // Return the HTTP redirect response
            return $server->completeAuthorizationRequest($authRequest, $response);

        }
    } catch (OAuthServerException $exception) {

        // All instances of OAuthServerException can be formatted into a HTTP response
        return $exception->generateHttpResponse($response);

    } catch (\Exception $exception) {

        // Unknown exception
        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write($exception->getMessage());
        return $response->withStatus(500)->withBody($body);

    }
})->setName('authorise');

$app->post('/login', function(Request $req, Response $res, array $args) {

    try {
        session_start();
        $postData = $req->getParsedBody();
        $usr = $postData["usr"];
        $pwd = $postData["pwd"];

        $sql = "SELECT * from users WHERE user=?";
        $ps = $this->db->prepare($sql);
        $ps->execute([$usr]);
        $row = $ps->fetch();

        if ($row != false) {
            if ($pwd == $row["password"]) {
                $_SESSION["authorised_user"] = $row["id"]; // set authorised user session variable equal to id so it can be used in UserEntity class

                return $res->withRedirect($this->router->pathFor('scopeAuthorisation'));
            } else {
                throw new Exception("No user registered by those credentials");
            }
        }
    } catch (Exception $e) {
        echo $e->getMessage();
        header("refresh:5;url=localhost/oauth_server/authorisation_server/login");
    }

})->setName('login');

$app->get('/scope_authorisation', function(Request $req, Response $res, array $args) {
    if(!isset($_SESSION["authorised_user"])){ //if user has not logged in redirect to login view else..
        $res = $view->render($res, 'login.phtml');
        return $res;
    } else {
        $sql = "SELECT name FROM oauth_clients where id=?";
        $ps = $this->db->prepare($sql);
        $ps->execute([$_SESSION["authorised_user"]]);
        $clientApplication = $ps->fetch();
        $_SESSION["clientApplication"] = $clientApplication; // retrieve client application name & save to session for View message
        $allscopes = $scopeRepository->returnAllScopes();
        $res = $view->render($res, "scope_auth.phtml", $allscopes); //render accepts array as an argument
    }

})->setName('scopeAuthorisation');

$app->get('/handle_scopes', function(Request $req, Response $res, array $args) {

})->setName('handle_scopes');

// Run the application
$app->run();
