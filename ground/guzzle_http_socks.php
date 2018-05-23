<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* guzzle_http_socks.php
* 
* Author: HFX 2018-05-23 17:32
*/
include '../vendor/autoload.php';

$httpClient = new \GuzzleHttp\Client([
    'verify' => false,
    'proxy' => 'socks5h://127.0.0.1:1080',
    'headers' => [
        'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
        'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.181 Safari/537.36',
        'accept-encoding' => 'gzip, deflate, br',
        'accept-language' => 'zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7,ar;q=0.6',
    ],
]);

$url = sprintf("https://twitter.com/i/profiles/show/%s/timeline/tweets", 'puzhiqiang');
$params = [
    'query' => [
        'include_available_features' => 0,
        'include_entities' => 0,
        'max_position' => '',
        'reset_error_state' => 'true',
    ]
];

$response = $httpClient->request("GET", $url, $params);
print_r($response->getHeaders());
echo $response->getBody()->getContents();
