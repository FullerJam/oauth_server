<?php
// error_reporting(E_ALL & ~E_NOTICE); // ignore all notices, as was corrupting json responses with "public key permissions are not correct, recommend changing to 600 or 660 instead of 666. Would be a problem with real auth server but as this is a proof of concept just ignoring it. 
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use Psr\Http\Message\ResponseInterface as Response; //slim req & res interfaces
use Psr\Http\Message\ServerRequestInterface as Request;
require '../vendor/autoload.php';
// Include all the Slim dependencies. Composer creates an 'autoload.php' inside the 'vendor' directory which will, in turn, include all required dependencies.

session_start(); // was using session start incorrectly? think i only need to declare once as i couldnt get past authorizescopes route

require_once '../Classes/Repositories/ClientRepository.php';
require_once '../Classes/Repositories/AccessTokenRepository.php';
require_once '../Classes/Repositories/ScopeRepository.php';
require_once '../Classes/Repositories/AuthCodeRepository.php';
require_once '../Classes/Repositories/RefreshTokenRepository.php'; // not needed
require_once '../Classes/Entities/ClientEntity.php';
require_once '../classes/Entities/UserEntity.php';
require_once '../Classes/Entities/AccessTokenEntity.php';
require_once '../Classes/Entities/ScopeEntity.php';
require_once '../Classes/Entities/AuthCodeEntity.php';
require_once '../Classes/Entities/RefreshTokenEntity.php';

$config = [
    'settings' => ['displayErrorDetails' => true],
];

// Create a new Slim App object. (v3 method)
$app = new \Slim\App($config);
// php renderer object for phtml

$container = $app->getContainer();

// $view = new \Slim\Views\PhpRenderer('./views/');
$container['view'] = function ($container) {
    return new \Slim\Views\PhpRenderer('./views/');
};

$container['db'] = function ($container) {
    $conn = new PDO("mysql:host=localhost;dbname=oauth2.0", "root", "");
    return $conn;
};

//created server container to follow example on github
$container['server'] = function ($container) {
    $db = $container->get('db'); //explicit ref to container instance
    // Init our repositories pass database through param
    $clientRepository = new ClientRepository($db); // instance of ClientRepositoryInterface
    $scopeRepository = new ScopeRepository($db); // instance of ScopeRepositoryInterface
    $accessTokenRepository = new AccessTokenRepository($db); // instance of AccessTokenRepositoryInterface
    $authCodeRepository = new AuthCodeRepository($db); // instance of AuthCodeRepositoryInterface
    $refreshTokenRepository = new RefreshTokenRepository($db); // instance of RefreshTokenRepositoryInterface

    $privateKey = new CryptKey('C:\xampp\htdocs\oauth\private\private.key', null, false);
    $encryptionKey = 'zIp8MjjxBRQc+CzRTEEbplaJOsIP0OhP0LyhMC+RyKY='; // generate 'openssl rand -base64 32' in console omit the 's

// Setup the authorization server
    $server = new \League\OAuth2\Server\AuthorizationServer(
        $clientRepository,
        $accessTokenRepository,
        $scopeRepository,
        $privateKey,
        $encryptionKey
    );
    // Enable the authentication code grant on the server with a token TTL of 1 hour
    $server->enableGrantType(
        new AuthCodeGrant(
            $authCodeRepository,
            $refreshTokenRepository,
            new \DateInterval('PT10M')
        ),
        new \DateInterval('PT8H')
    );
    return $server;
};
// $grant->setRefreshTokenTTL(new \DateInterval('PT1H')); // refresh tokens will expire after 1 hour

//client requests an access token
$app->post('/access_token', function (Request $request, Response $response, array $args) {
    try {
        // Try to respond to the request
        $this->server->respondToAccessTokenRequest($request, $response);
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
$app->get('/authorize', function (Request $request, Response $response, array $args) {

    try {
        //getQuery() returns query as string - getQueryParams() returns associative array of those params -> http://www.slimframework.com/docs/v3/objects/request.html
        //$queryParams = $request->getQueryParams(); //flow part one params [response_type, client_id, redirect_uri, scope, state] -> https://oauth2.thephpleague.com/authorization-server/auth-code-grant/

        //save all params to session variables
        $_SESSION["oauth_qp"] = [];
        $qpFields = ["response_type", "client_id", "redirect_uri", "state"]; // ignoring scope as returning all scopes
        foreach ($qpFields as $qpField) {
            $_SESSION["oauth_qp"][$qpField] = $_GET[$qpField];
        }

        // The auth request object can be serialized and saved into a user's session.

        // You will probably want to redirect the user at this point to a login endpoint.

        if (!isset($_SESSION['authorised_user'])) {
            //login --
            $response = $this->view->render($response, 'login.phtml');
            return $response;
        } else {
            // Validate the HTTP request and return an AuthorizationRequest object.
            $authRequest = $this->server->validateAuthorizationRequest($request);

            // Once the user has logged in set the user on the AuthorizationRequest
            $authRequest->setUser(new UserEntity($_SESSION['authorised_user'], $this->db)); // an instance of UserEntityInterface

            unset($_SESSION['authorised_user']);
            //user has approved in some capacity if they have come this far
            // (true = approved, false = denied)
            $authRequest->setAuthorizationApproved(true);
            // Return the HTTP redirect response
            return $this->server->completeAuthorizationRequest($authRequest, $response);
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

$app->post('/login', function (Request $request, Response $response, array $args) {

    try {
        $postData = $request->getParsedBody();
        $email = $postData["email"];
        $pwd = $postData["pwd"]; // retrieve login details

        $sql = "SELECT password, id FROM users WHERE email=?";
        $ps = $this->db->prepare($sql); //ps preparedstatement
        $ps->execute([$email]);
        $row = $ps->fetch(); // find user that matches posted credentials

        if ($row != false) { //if email exists
            if (password_verify($pwd, $row["password"])) { // https://www.php.net/manual/en/function.password-verify.php
                $_SESSION["logged_in_user"] = $row["id"]; // set authorised user session variable equal to id so it can be used in UserEntity class
                return $response->withRedirect($this->router->pathFor('scopeAuthorisation'));
            } else {
                throw new Exception("No user registered by those credentials,<br> please try again.");
            }
        }
    } catch (Exception $error) {
        $_SESSION["oauth_qp"]["error"] = $error->getMessage();
        $queryParams = http_build_query($_SESSION["oauth_qp"]);
        return $response->withRedirect($this->router->pathFor('authorise') . "?$queryParams");
        // header("refresh:5;url=authorize?"+$queryParams);
    }

})->setName('login');

$app->get('/scope_authorisation', function (Request $request, Response $response, array $args) {
    if (!isset($_SESSION["logged_in_user"])) { //if user has not logged in redirect to login view else..
        $response = $this->view->render($response, 'login.phtml');
        return $response;
    } else {
        $sql = "SELECT name FROM oauth_clients where id=?";
        $ps = $this->db->prepare($sql); //ps preparedstatement
        $clientId = $_SESSION["oauth_qp"]["client_id"];
        $ps->execute([$clientId]);
        $row = $ps->fetch();
        $_SESSION["clientApplication"] = $row;

        // retrieve client application name & save to session for View message
        //real Oauth server would use Scopes session variable to set requested scopes from client in scope_auth view. just a demo
        $newScopeRepository = new ScopeRepository($this->db);
        $allScopes = $newScopeRepository->returnAllScopes();
        $this->view->render($response, "scope_auth.phtml", array('allScopes' => $allScopes)); //render accepts array as an argument. did incorrectly http://www.slimframework.com/docs/v2/view/rendering.html
    }

})->setName('scopeAuthorisation');

$app->post('/handle_scopes', function (Request $request, Response $response, array $args) {
    if (isset($_SESSION["logged_in_user"]) && ($_POST["ApproveScopes"] == "true")) { //check that user has atleast provided write permission & is logged in
        $scopesArray = $_POST["scopes"];

        $_SESSION['authorised_user'] = $_SESSION["logged_in_user"]; // set user as authorised

        $_SESSION["oauth_qp"]["scope"] = implode(" ", $scopesArray);
        $queryParams = http_build_query($_SESSION["oauth_qp"]);
        unset($_SESSION["oauth_qp"]);

        return $response->withRedirect($this->router->pathFor('authorise') . "?$queryParams");
    } else {
        $error = ["You need to atleast authorise read access to use this service"];
        $newScopeRepository = new ScopeRepository($this->db);
        $allScopes = $newScopeRepository->returnAllScopes();
        $this->view->render($response, "scope_auth.phtml", array('allScopes' => $allScopes, 'error' => $error));
        // return $response->withRedirect($this->router->pathFor('scopeAuthorisation')); // changed out for $view->render as I wasnt passing $error
    }
})->setName('handle_scopes');

// Run the application
$app->run();

// $_SESSION["res_type"] = $queryParams["response_type"];
//         $_SESSION["client_id"] = $queryParams["client_id"];
//         $_SESSION["redirect_uri"] = $queryParams["redirect_uri"];
//         $_SESSION["scope"] = $queryParams["scope"];
//         $_SESSION["state"] = $queryParams["state"];
