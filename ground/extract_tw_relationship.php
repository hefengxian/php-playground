<?php
/*
* Copyright © 2019-present, Knowlesys, Ltd.
* All rights reserved.
* 
* extract_tw_relationship.php
* 
* Author: HFX 2019-01-03 15:24
*/

include '../vendor/autoload.php';

$client = new \GuzzleHttp\Client([
    'verify' => false,
    'headers' => [
        // 'authorization' => 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA',
        'authorization' => 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs=1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA',
    ],
    'proxy' => 'socks5h://127.0.0.1:1080',
]);

function getFollowers(\GuzzleHttp\Client $client)
{
    $response = $client->request('GET', 'https://api.twitter.com/1.1/followers/list.json', [
        'query' => [
            'include_profile_interstitial_type' => '1',
            'include_blocking' => '1',
            'include_blocked_by' => '1',
            'include_followed_by' => '1',
            'include_want_retweets' => '1',
            'include_mute_edge' => '1',
            'include_can_dm' => '1',
            'include_can_media_tag' => '1',
            'skip_status' => '1',
            'cursor' => '-1',
            'user_id' => '428333',
            'count' => '50',
        ]
    ]);

    echo $response->getBody()->getContents();
}


function getFriends(\GuzzleHttp\Client $client)
{
    $response = $client->request('GET', 'https://api.twitter.com/1.1/friends/list.json', [
        'query' => [
            'include_profile_interstitial_type' => 1,
            'include_blocking' => 1,
            'include_blocked_by' => 1,
            'include_followed_by' => 1,
            'include_want_retweets' => 1,
            'include_mute_edge' => 1,
            'include_can_dm' => 1,
            'include_can_media_tag' => 1,
            'skip_status' => 1,
            'cursor' => -1,
            'user_id' => 428333,
            'count' => 5,
        ]
    ]);

    echo $response->getBody()->getContents();
}



getFriends($client);






