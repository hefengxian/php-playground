<?php
/*
* Copyright © 2019-present, Knowlesys, Ltd.
* All rights reserved.
* 
* import_data.php
* 
* Author: HFX 2019-05-10 15:24
*/

use Elasticsearch\ClientBuilder;

require_once '../../vendor/autoload.php';

error_reporting(E_ERROR);


function genParams($hits)
{
    $params = [];
    foreach ($hits as $hit) {
        $item = $hit['_source'];
        $params['body'][] = [
            'index' => [
                '_index' => 'article_detail',
                '_id' => $item['article_detail_id'],
            ]
        ];
        // 映射字段
        $params['body'][] = [
            'article_abstract' => $item['article_abstract'],
            'article_abstract_fingerprint' => $item['article_abstract_fingerprint'],
            'article_author' => empty($item['article_author']) ? $item['article_author_list'] : $item['article_author'],
            'article_content' => $item['article_content'],
            'article_content_fingerprint' => $item['article_content_fingerprint'],
            'article_detail_id' => $item['article_detail_id'],
            'article_pubtime' => empty($item['article_pubtime']) ? $item['article_pubtime_list'] : $item['article_pubtime'],
            'article_pubtime_str' => empty($item['article_pubtime_str']) ? $item['article_pubtime_str_list'] : $item['article_pubtime_str'],
            'article_search_keywords' => $item['article_search_keywords'],
            'article_source' => empty($item['article_source']) ? $item['article_source_list'] : $item['article_source'],
            'article_title' => empty($item['article_title']) ? $item['article_title_list'] : $item['article_title'],
            'article_title_fingerprint' => $item['article_title_fingerprint'],
            'article_url' => $item['article_url'],
            'article_url_md5_id' => $item['article_url_md5_id'],
            'author_raw_id' => $item['author_raw_id'],
            'author_raw_id_list' => $item['author_raw_id_list'],
            'city_area_code' => $item['city_area_code'],
            'country_code' => $item['country_code'],
            'district_area_code' => $item['district_area_code'],
            'domain_code' => $item['domain_code'],
            'down_count' => $item['down_count'],
            'extracted_time' => $item['extracted_time'],
            'forward_count' => $item['forward_count'],
            'host' => $item['host'],
            'is_extract_after_detail' => $item['is_extract_after_detail'],
            'is_html_content' => $item['is_html_content'],
            'is_with_content' => $item['is_with_content'],
            'language_code' => $item['language_code'],
            'language_code_list' => $item['language_code_list'],
            'last_update_time' => $item['last_update_time'],
            'like_count' => $item['like_count'],
            'media_type_code' => $item['media_type_code'],
            'message' => $item['message'],
            'microblog_type' => $item['microblog_type'],
            'node_id' => $item['node_id'],
            'order_no_in_search_result' => $item['order_no_in_search_result'],
            'province_area_code' => $item['province_area_code'],
            'record_md5_id' => $item['record_md5_id'],
            'refpage_type' => $item['refpage_type'],
            'refpage_url_id' => $item['refpage_url_id'],
            'reply_count' => $item['reply_count'],
            'tags' => $item['tags'],
            'up_count' => $item['up_count'],
            'view_count' => $item['view_count'],
            'website_no' => $item['website_no'],
        ];
    }
    return $params;
}


$client231 = ClientBuilder::create()
    ->setHosts(['http://192.168.1.231:9200'])
    ->build();

$client48 = ClientBuilder::create()
    ->setHosts(['http://192.168.1.48:9200'])
    ->build();

$pageSize = 5000;

// 查询 231 的数据
$response231 = $client231->search([
    'index' => 'article_detail',
    'scroll' => '1m',
    'body' => [
        'query' => [
            'bool' => [
                'must' => [
                    'exists' => [
                        'field' => 'article_content'
                    ]
                ],
                'filter' => [
                    'range' => [
                        'extracted_time' => [
                            'gte' => '2019-02-01T08:00:00.000Z',
                            'lte' => '2019-03-01T08:00:00.000Z',
                        ]
                    ]
                ]
            ],
        ],
        'size' => $pageSize,
    ]
]);

// 处理第一次请求
// $scrollID = $response231['_scroll_id'];
$hits = isset($response231['hits']['hits']) ? $response231['hits']['hits'] : [];
// $params = genParams($hits);
// $client48->bulk($params);
//
// $totalCount = $response231['hits']['total'];
// $returnCount = count($hits);
// echo sprintf('total: %s, return: %s, current scroll id: %s' . PHP_EOL, $totalCount, $returnCount, $scrollID);

while (isset($response231['hits']['hits']) && !empty($response231['hits']['hits'])) {

    $hits = isset($response231['hits']['hits']) ? $response231['hits']['hits'] : [];
    $scrollID = $response231['_scroll_id'];
    $totalCount = $response231['hits']['total'];
    $returnCount = count($hits);

    // 转换数据
    $params = genParams($hits);
    $indexStartTime = microtime(true);
    $client48->bulk($params);
    $indexEndTime = microtime(true);
    echo sprintf('index cost: %sms, current index: %s' . PHP_EOL, round(($indexEndTime - $indexStartTime) * 1000), $returnCount);

    $queryStartTime = microtime(true);
    $response231 = $client231->scroll([
        'scroll_id' => $scrollID,
        'scroll' => '1m'
    ]);
    $queryEndTime = microtime(true);
    echo sprintf('query cost: %sms, total: %s, current return: %s' . PHP_EOL, round(($queryEndTime - $queryStartTime) * 1000), $totalCount, $returnCount);

    echo PHP_EOL;
}


