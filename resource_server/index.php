<?php

// Include all the Slim dependencies. Composer creates an 'autoload.php' inside
// the 'vendor' directory which will, in turn, include all required dependencies.
require '../vendor/autoload.php';


// Create a new Slim App object. (v3 method)
$app = new \Slim\App;

// $container = $app->getContainer();

$conn = new PDO("mysql:host=localhost;dbname=oauth2.0", "root", "");

// $container['db'] = function () use ($conn) {
//     return $conn;
// };

$publicKeyPath = '..\authorisation_server\public.key';

// Setup the authorization server
$server = new \League\OAuth2\Server\ResourceServer(
    $accessTokenRepository,
    $publicKeyPath
);


new \League\OAuth2\Server\Middleware\ResourceServerMiddleware($server);

// Setup up routes
$app->get('/read', function ($req, $res, array $args) use($conn) {
    $res->getBody()->write('Read permissions route');
    return $res;
});

$app->get('/readwrite', function ($req, $res, array $args) use($conn) {
    $res->getBody()->write('Read & Write permissions route');
    return $res;
});

jsl
// Run the application
$app->run();