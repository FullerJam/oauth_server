<?php
use Psr\Http\Message\ResponseInterface as Response; //slim req & res interfaces
use Psr\Http\Message\ServerRequestInterface as Request;
// Include all the Slim dependencies. Composer creates an 'autoload.php' inside
// the 'vendor' directory which will, in turn, include all required dependencies.
require '../vendor/autoload.php';
require_once '../Classes/Repositories/AccessTokenRepository.php';

$config = [
    'settings' => ['displayErrorDetails' => true],
];

// Create a new Slim App object. (v3 method)
$app = new \Slim\App($config);
// php renderer object for phtml

$container = $app->getContainer();

$container['db'] = function () {
    $conn = new PDO('mysql:host=localhost;dbname=oauth2.0', 'root', '');
    return $conn;
};


// Setup the resource server
$container['server'] = function ($container) use($app) {
    $db = $container->get('db');
    $server = new \League\OAuth2\Server\ResourceServer(
        $accessTokenRepository = new AccessTokenRepository($db),
        $publicKeyPath = 'C:\xampp\htdocs\oauth\oauth_server\authorisation_server\public.key'
    );
    return $server;
};
$server = $container->get('server');

$app->add(new \League\OAuth2\Server\Middleware\ResourceServerMiddleware($server));
// automatically validates request header
// If the access token is valid the following attributes will be set on the ServerRequest:
// oauth_access_token_id - the access token identifier
// oauth_client_id - the client identifier
// oauth_user_id - the user identifier represented by the access token
// oauth_scopes - an array of string scope identifiers
// If the authorization is invalid an instance of OAuthServerException::accessDenied will be thrown.


// Setup up routes
$app->post('/read', function ($request, $response, array $args) {
    try{
        if(in_array("email", $request->getAttribute("scopes")))
        $user_id = $_POST["oauth_user_id"];
        $sql = "SELECT email FROM users WHERE id=?";
        $ps = $this->db->prepare($sql);
        $result = $ps->execute([$id]);
        return $response->withJson(['email' => $result["email"]], 200);
    }catch(\Exception $exception){
        return $response->withJson(["msg"=>$e->getMessage()], 500);
    }

});

$app->post('/readwrite', function ($request, $response, array $args)  {
    return $response->withJson(['msg' => 'Read & Write permissions route'], 200);
});

// Run the application
$app->run();
