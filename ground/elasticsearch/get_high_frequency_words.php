<?php
/*
* Copyright © 2019-present, Knowlesys, Ltd.
* All rights reserved.
* 
* get_high_frequency_words.php
* 
* Author: HFX 2019-06-04 14:13
*/

require(__DIR__ . '/../../vendor/autoload.php');

// 设置错误级别，设置不限内存、不限时间
error_reporting(E_ALL);
ini_set('memory_limit', -1);
set_time_limit(0);


use Elasticsearch\ClientBuilder;

// 初始化 ES 客户端
$esClient = ClientBuilder::create()
    ->setHosts([
        'http://192.168.1.47:9200',
        'http://192.168.1.48:9200'
    ])
    ->build();

$indexName = 'ks-article-20190520';


// 2 个字的

$params = [
    'index' => $indexName,
    'body' => [
        /*'query' => [
            'bool' => [
                'must' => [
                    'nested' => [
                        'path' => 'Subject_Stat',
                        'query' => [
                            'term' => [
                                'Subject_Stat.Subject_ID' => [
                                    'value' => $subject['Subject_ID']
                                ]
                            ]
                        ]
                    ]
                ],
                'filter' => [
                    'range' => [
                        'Extracted_Time' => [
                            'gte' => $startTime,
                            'lte' => $endTime
                        ]
                    ]
                ]
            ]
        ],*/
        'size' => 0,
        'aggs' => [
            'hot_words' => [
                'terms' => [
                    'field' => 'Article_Content',
                    'size' => 5000,
                    'include' => '.{2,}',   // 表示字的个数要大于 2
                    'exclude' => '[0-9]*',
                ]
            ]
        ]
    ],
];

$response = $esClient->search($params);
$buckets = $response['aggregations']['hot_words']['buckets'];

$hotWords = [];
foreach ($buckets as $bucket) {
    // $hotWords[] = "{$bucket['key']} {$bucket['doc_count']}";
    $hotWords[] = $bucket['key'];
}
file_put_contents(__DIR__ . '/exclude.dic', implode(PHP_EOL, $hotWords));
// var_dump($hotWords);