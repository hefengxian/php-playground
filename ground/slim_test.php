<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* slim_test.php
* 
* Author: HFX 2018-06-04 15:52
*/

require_once ('../vendor/autoload.php');


$app = new \Slim\App([]);

$app->get('/users/{id}', function(\Slim\Http\Request $request, \Slim\Http\Response $response, array $args) {
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*')
        ->withAddedHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept')
        ->withAddedHeader('Access-Control-Allow-Methods', '*');
    return $response->withJson(['hello' => 'word']);
});


$app->run();