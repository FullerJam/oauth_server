<?php
use League\OAuth2\Server\CryptKey; 
use League\OAuth2\Server\ResourceServer;
//slim req & res interfaces
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

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
$container['server'] = function ($container) use ($app) {
    $db = $container->get('db');
    $publicKeyPath = new CryptKey('C:\xampp\htdocs\oauth\oauth_server\authorisation_server\public.key', null, false);
    $server = new \League\OAuth2\Server\ResourceServer(
        $accessTokenRepository = new AccessTokenRepository($db),
        $publicKeyPath
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
    try {
        if (in_array("read", $request->getAttribute("oauth_scopes"))) {
            $user_id = $request->getAttribute("oauth_user_id");
            $sql = "SELECT email FROM users WHERE id=?";
            $ps = $this->db->prepare($sql);
            $ps->execute([$user_id]);
            $result = $ps->fetch();
            return $response->withJson(['email' => $result["email"], 'username' => "username"], 200);
        } else {
            throw new Exception('Something went wrong, check approved scopes.');
        }
    } catch (\Exception $exception) {
        return $response->withJson(["msg" => $e->getMessage()]);
    }

});


$app->post('/get_messages', function ($request, $response, array $args) {

    try {
        if (in_array("email", $request->getAttribute("oauth_scopes"))) {
            $sql = "SELECT * FROM message_board";
            $ps = $this->db->prepare($sql);
            $ps->execute();
            $rows = $ps->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) < 1) {
                return $response->withJson(["msg" => "No messages to display"], 204);
            } else {
                return $result->withJson([$rows], 200);
            }
            // return $response->withJson(['id' => $result["id"], 'email' => $result['email'], 'time' => $result['time'], 'message' => $result['message']], 200);
        }
    } catch (\Exception $exception) {
        return $response->withJson(["msg" => $e->getMessage()], 500);
    }
});

$app->post('/set_message', function ($request, $response, array $args) {

    try {
        if (in_array("read", $request->getAttribute("oauth_scopes"))) {
            $postData = $request->getParsedBody(); //message
            $message = $postData["message"];
            // if ($message) { // if no message populated dont record the db entry
            //     $date = new DateTime(); // time
            //     $user_id = $request->getAttribute("oauth_user_id"); //userId
            //     // $sql = "SELECT email FROM users WHERE id=?";
            //     // $ps = $this->db->prepare($sql);
            //     // $ps->execute([$user_id]);
            //     // $result = $ps->fetch();
            //     // $sql2 = "INSERT INTO message_board (userId, email, time, message) VALUES (?,?,?,?)";
            //     // $ps2 = $this->db->prepare($sql2);
            //     // $ps2->execute([$user_id, $result['email'], $date->getTimestamp(), $message]);
            //     return $response->withJson(["msg" => $message, "userId" => $user_id], 200);
            // } else {
            //     return $response->withJson(["msg" => "You must provide a message"], 400);
            //     // throw new Exception("You must provide a message");
            // }
            return $response->withJson(["msg" => $message], 200);
        } else {
            return $response->withJson(["msg" => "else statement"], 200);
        }
        
    } catch (\Exception $exception) {
        return $response->withJson(["msg" => $e->getMessage()], 500);
    }
});

$app->post('/del_message', function ($request, $response, array $args) {

    try {
        if (in_array("email", $request->getAttribute("oauth_scopes"))) {
            $sql = "DELETE FROM message_board WHERE id=? AND time=?";

        }
    } catch (\Exception $exception) {
        return $response->withJson(["msg" => $e->getMessage()], 500);
    }
});
// Run the application
$app->run();
