<?php

// Include all the Slim dependencies. Composer creates an 'autoload.php' inside
// the 'vendor' directory which will, in turn, include all required dependencies.
require '../vendor/autoload.php';


// Create a new Slim App object. (v3 method)
$app = new \Slim\App;

// $container = $app->getContainer();

// $container['db'] = function() {

//     $conn = new PDO("mysql:host=localhost;dbname=dftitutorials", "dftitutorials", "dftitutorials");
//     return $conn;
// };

//Setup up routes
// $app->get('/hello', function ($req, $res, array $args) {
//     $res->getBody()->write('Hello World from Slim!');
//     return $res;
// });

// Run the application
$app->run();