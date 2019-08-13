<?php
/*
* Copyright © 2019-present, Knowlesys, Ltd.
* All rights reserved.
* 
* hot_words.php
* 
* Author: HFX 2019-05-21 10:54
*/

use Elasticsearch\ClientBuilder;
use Knowlesys\Database;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once(__DIR__ . '/../../vendor/autoload.php');

// 设置错误级别，设置不限内存、不限时间
error_reporting(E_ALL);
ini_set('memory_limit', -1);
set_time_limit(0);

// 初始化日志组件
$logger = new Logger('HotWords');
$logger->pushHandler(new StreamHandler(__DIR__ . '/hot_words.log'));

// 初始化 ES 客户端
$esClient = ClientBuilder::create()
    ->setHosts([
        'http://192.168.1.47:9200',
        'http://192.168.1.48:9200'
    ])
    ->build();

// 初始化数据库连接
$dbConfig = array_merge(Database::$defaultConfig, ['host' => '192.168.1.116']);
$db = Database::getConnection($dbConfig);

// 统计日期
$startTime = '2019-05-12 00:00:00';
$endTime = '2019-05-18 23:59:59';

// 获取 客户&主题 信息
$logger->info('开始获取所有已激活客户信息');
$clients = $db->createQueryBuilder()
    ->select('c.*')
    ->from('client', 'c')
    ->where('c.Is_Active=1')
    ->execute()
    ->fetchAll();

$indexName = 'ks-article-20190520';

$highFrequencyWordsDic = file_get_contents(__DIR__ . '/exclude.dic');
$highFrequencyWords = explode(PHP_EOL, $highFrequencyWordsDic);

$params = [
    'index' => $indexName,
    'body' => [
        'query' => [
            'bool' => [
                'must' => [],
                /*'must_not' => [
                   'terms' => [
                       'Article_Content' => $highFrequencyWords
                   ]
                ],*/
                /*'filter' => [
                    'range' => [
                        'Extracted_Time' => [
                            'gte' => $startTime,
                            'lte' => $endTime
                        ]
                    ]
                ]*/
            ]
        ],
        'size' => 0,
        'aggs' => [
            'hot_words' => [
                'terms' => [
                    'field' => 'Article_Content',
                    'size' => 100,
                    'include' => '.{2,}',   // 表示字的个数要大于 1
                    'exclude' => '',
                ]
            ]
        ]
    ],
];

// 逐个客户处理
$logger->info('逐个客户处理');
foreach ($clients as $client) {
    $clientID = $client['Client_ID'];
    $logger->info("当前处理客户: {$client['Client_Name']}");

    // 停止词，每个客户都有自己独特的停止词
    $logger->info("获取当前客户停止词");
    $stopWordQuery = $db->createQueryBuilder()
        ->select('bw.Word_Express')
        ->from('black_word', 'bw')
        ->leftJoin('bw', 'black_list', 'bl', 'bl.Black_List_ID=bw.Black_List_ID')
        ->where('bl.Client_ID = ?')
        ->setParameter(0, $clientID);

    $stopWordList = $stopWordQuery->execute()
        ->fetchAll();

    // 处理正则
    $stopWords = $highFrequencyWords;
    $stopWords[] = '[0-9]*';      // 纯数字
    foreach ($stopWordList as $stopWord) {
        $express = $stopWord['Word_Express'];
        $stopWord[] = preg_replace('/[-[\]{}()*+?.,\\^$|#\s]/is', '\\$&', $express);
    }
    $stopWordRegPattern = implode('|', $stopWords);
    $params['body']['aggs']['hot_words']['terms']['exclude'] = $stopWordRegPattern;
    $logger->info("获取当前客户停止词正则表达式：{$stopWordRegPattern}");

    $logger->info("获取客户下的主题");
    $subjectQuery = $db->createQueryBuilder()
        ->select('s.*')
        ->from('subject', 's')
        ->leftJoin('s', 'subject_category', 'sc', 's.Subject_Category_ID=sc.Subject_Category_ID')
        ->where('sc.Client_ID = ?')
        ->andWhere('sc.Is_Enabled=1')
        ->andWhere('s.Is_Enabled=1')
        ->setParameter(0, $clientID);

    $subjects = $subjectQuery
        ->execute()
        ->fetchAll();

    foreach ($subjects as $subject) {
        // 组合 ES 查询参数
        $subjectCondition = [
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
        ];
        $params['body']['query']['bool']['must'][] = $subjectCondition;

        // 执行聚集查询
        $logger->debug('执行聚集查询参数: ' . json_encode($params));
        $esResponse = $esClient->search($params);
        // 清理参数
        $params['body']['query']['bool']['must'] = [];
        // 将结果写入到文本
        @file_put_contents(__DIR__ . "/hot_words/{$subject['Subject_ID']}.json", json_encode($esResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $logger->info("主题 `{$subject['Subject_Name']}` 热词统计完毕");
    }
    $logger->info("客户 `{$client['Client_Name']}` 热词统计完毕");
}

$logger->info("所有客户热词统计完毕");