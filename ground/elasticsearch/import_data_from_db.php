<?php
/*
* Copyright © 2019-present, Knowlesys, Ltd.
* All rights reserved.
* 
* import_data_from_db.php
* 
* Author: HFX 2019-05-15 18:25
*/

use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Knowlesys\Database;


include_once '../../vendor/autoload.php';

error_reporting(E_ERROR);
set_time_limit(0);
ini_set('memory_limit', -1);

$dbConfig = array_merge(Database::$defaultConfig, ['host' => '192.168.1.119']);
$db = Database::getConnection($dbConfig);
$esClient = ClientBuilder::create()
    ->setHosts(['http://192.168.1.48:9200'])
    ->build();

$startTime = strtotime('2019-05-13 12:44:00');
$stopTime = time();
$format = 'Y-m-d H:i:s';


while ($startTime < $stopTime) {
    $_currentTaskStart = microtime(true);
    $condition = sprintf("ad.extracted_time between '%s' and '%s'", date($format, $startTime), date($format, $startTime + 59));
    echo sprintf('[%s] [start]本次任务查询条件: %s', date('Y-m-d H:i:s'), $condition), PHP_EOL;

    // 文章基本信息
    $articles = getArticleDetail($db, $condition);

    // 文章操作信息
    $operations = getOperation($db, $condition);

    // 分类信息
    $subjects = getStatSubject($db, $condition);

    // 标签信息
    $tags = getTag($db, $condition);

    // 映射信息
    $_indexStart = microtime(true);
    $params = [];
    foreach ($articles as &$article) {
        $params['body'][] = [
            'index' => [
                '_index' => 'ks_articles',
                '_id' => $article['Article_Detail_ID'],
            ]
        ];

        // 转 Boolean
        $article['Is_With_Content'] = $article['Is_With_Content'] > 0;
        $article['Is_Extract_After_Detail'] = $article['Is_Extract_After_Detail'] > 0;
        $article['Is_Html_Content'] = $article['Is_Html_Content'] > 0;

        // TODO 否则会被修改 Mapping
        // 数字类型要转换
        // NULL 要转换

        $article['Operation'] = [];
        foreach ($operations as $operation) {
            if ($article['Article_Detail_ID'] == $operation['Article_Detail_ID']) {
                // 转 Boolean
                $operation['User_Need_Extract_Reply'] = $operation['User_Need_Extract_Reply'] > 0;
                $article['Operation'][] = $operation;
            }
        }

        $article['Subject_Stat'] = [];
        foreach ($subjects as $subject) {
            if ($article['Article_Detail_ID'] == $subject['Article_Detail_ID']) {
                // 转 Boolean
                $subject['Is_Valid'] = $subject['Is_Valid'] > 0;
                $article['Subject_Stat'][] = $subject;
            }
        }

        $article['Tag'] = array_values(array_filter($tags, function ($tag) use ($article) {
            return $article['Article_Detail_ID'] == $tag['Article_Detail_ID'];
        }));

        $params['body'][] = $article;
    }

    // file_put_contents('/home/hfx/Desktop/Temp/es_sample.json', json_encode($articles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    try {
        $indexResponse = $esClient->bulk($params);
    } catch (Exception $e) {
        var_dump($e->getMessage());
        exit;
    }

    // 统计索引情况
    $took = $indexResponse['took'];
    $indexStatistics = [];
    foreach ($indexResponse['items'] as $respItem) {
        $tmp = $respItem['index'];
        $indexStatistics[$tmp['result']][] = $tmp['status'];
    }
    $statisticParts = [
        'took: ' . $took,
    ];
    foreach ($indexStatistics as $rs => $stats) {
        $statisticParts[] = "$rs: " . count($stats);
    }
    echo sprintf("[%s] 索引结果统计 %s", date('Y-m-d H:i:s'), implode(', ', $statisticParts)), PHP_EOL;

    $indexCost = round((microtime(true) - $_indexStart) * 1000);
    echo sprintf("[%s] 本次索引耗时: %sms, 共处理: %s 篇文章", date('Y-m-d H:i:s'), $indexCost, count($articles)), PHP_EOL;

    $startTime += 60;
    $cost = round((microtime(true) - $_currentTaskStart) * 1000);
    echo sprintf("[%s] [end]本次查询任务总耗时: %sms, 共处理: %s 篇文章", date('Y-m-d H:i:s'), $cost, count($articles)), PHP_EOL, PHP_EOL;
}


/**
 * 查询文章相关的一对一的信息
 *
 * @param Connection $db 数据库链接
 * @param string $condition 条件
 * @return array
 */
function getArticleDetail(Connection $db, $condition)
{
    $_start = microtime(true);
    $fields = [
        'IFNULL(ac.Article_Abstract, ad.Article_Abstract)       AS Article_Abstract',
        'ad.Article_Abstract_FingerPrint',
        'IFNULL(ac.Article_Author, ad.Article_Author)         AS Article_Author',
        'ad.Article_Detail_ID',
        'IFNULL(ac.Article_PubTime, ad.Article_PubTime)         AS Article_PubTime',
        'IFNULL(ac.Article_PubTime_Str, ad.Article_PubTime_Str) AS Article_PubTime_Str',
        'ad.Article_Search_Keywords',
        'IFNULL(ac.Article_Source, ad.Article_Source)         AS Article_Source',
        'IFNULL(ac.Article_Title, ad.Article_Title)             AS Article_Title',
        'ad.Article_Title_FingerPrint',
        'ad.Article_URL',
        'ad.Article_URL_MD5_ID',
        'IFNULL(ac.Author_Raw_ID, ad.Author_Raw_ID)         AS Author_Raw_ID',
        'ad.Domain_Code',
        'ad.Extracted_Time',
        'ad.Is_With_Content',
        'ad.Language_Code',
        'ad.Media_Type_Code',
        'ad.Microblog_Type',
        'ad.Node_ID',
        'ad.Order_No_In_Search_Result',
        'ad.Record_MD5_ID',
        'ad.RefPage_Type',
        'ad.RefPage_URL_ID',
        'ad.Transferred_Time',
        'ad.Website_No',

        // from article_content
        'ac.Article_Content',
        'ac.Article_Content_FingerPrint',
        'ac.Article_Content_ID',
        'ac.Is_Extract_After_Detail',
        'ac.Is_Html_Content',

        // from article_number
        'an.Article_Number_ID',
        'an.Down_Count',
        'an.Forward_Count',
        'an.Last_Update_Time',
        'an.Like_Count',
        'an.Reply_Count',
        'an.Up_Count',
        'an.View_Count',

        // from article_mention_area
        'ama.Article_Mention_Area_ID',
        'ama.City_Area_Code',
        'ama.Country_Code',
        'ama.District_Area_Code',
        'ama.Province_Area_Code',
    ];

    $query = $db->createQueryBuilder();
    $query->select(implode(',', $fields))
        ->from('article_detail', 'ad')
        ->where($condition);

    $leftJoins = [
        ['ad', 'article_number', 'an', 'an.Article_Record_MD5_ID=ad.Record_MD5_ID'],
        ['ad', 'article_mention_area', 'ama', 'ama.Article_Record_MD5_ID=ad.Record_MD5_ID'],
        ['ad', 'article_content', 'ac', 'ac.Article_Record_MD5_ID=ad.Record_MD5_ID'],
        // 地域
        // ['ama', 'country', 'ctry', 'ctry.Country_Code=ama.Country_Code'],
        // ['ama', 'province', 'p', 'p.Area_Code=ama.Province_Area_Code'],
        // ['ama', 'city', 'cty', 'cty.Area_Code=ama.City_Area_Code'],
        // ['ama', 'district', 'distr', 'distr.Area_Code=ama.District_Area_Code'],

        // ['ad', 'media_type', 'mt', 'mt.Media_Type_Code=ad.Media_Type_Code'],
        // ['ad', 'domain', 'd', 'ad.Domain_Code=d.Domain_Code'],
    ];

    // join 的表
    foreach ($leftJoins as $leftJoin) {
        $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
    }

    // echo $query->getSQL();exit;

    $rst = $query->execute()
        ->fetchAll();
    $cost = round((microtime(true) - $_start) * 1000);

    echo sprintf("[%s] 获取文章基础信息耗时: %sms, fetched count: %s", date('Y-m-d H:i:s'), $cost, count($rst)), PHP_EOL;
    return $rst;
}


/**
 * 获取分类信息
 *
 * @param Connection $db
 * @param string $condition
 * @return array
 */
function getStatSubject(Connection $db, $condition)
{
    $_start = microtime(true);
    $fields = [
        'sas.Article_Detail_ID',
        'sas.Client_ID',
        'sas.Created_Time',
        'sas.Emotion_Type',
        'sas.Is_Valid',
        'sas.Junk_Score',
        'sas.Relative_Score',
        'sas.Sentiment_Score',
        'sas.Similar_Record_Oldest_ID',
        'sas.Source_Type',
        'sas.Stat_Article_Subject_ID',
        'sas.Subject_ID',
        'sas.Total_Score',
    ];
    $result = [];

    // 去 100 个分类表中查询数据
    for ($i = 0; $i < 100; $i++) {
        $tableName = 'stat_article_subject_' . $i;

        $query = $db->createQueryBuilder()
            ->select(implode(',', $fields))
            ->from($tableName, 'sas')
            ->where($condition);

        // join 的表
        $leftJoins = [
            ['sas', 'article_detail', 'ad', 'sas.Article_Detail_ID=ad.Article_Detail_ID'],
        ];
        foreach ($leftJoins as $leftJoin) {
            $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
        }

        // echo $query->getSQL();exit;
        $rst = $query->execute()
            ->fetchAll();

        foreach ($rst as $item) {
            $result[] = $item;
        }
    }

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 获取文章所有分类信息耗时: %sms, fetched count: %s", date('Y-m-d H:i:s'), $cost, count($result)), PHP_EOL;

    return $result;
}


/**
 * 获取操作信息
 *
 * @param Connection $db
 * @param string $condition
 * @return array
 */
function getOperation(Connection $db, $condition)
{
    $_start = microtime(true);
    $fields = [
        'ao.Article_Detail_ID',
        // 'ao.Article_Extracted_Time',
        // 'ao.Article_Operation_ID',
        // 'ao.Article_PubTime',
        'ao.Client_ID',
        'ao.Content_Class_ID',
        'ao.Followup_Remark',
        'ao.Followup_Status',
        'ao.Upload_Status',
        'ao.User_Audit_Result',
        'ao.User_Confirm_Emotion_Type',
        'ao.User_ID',
        'ao.User_Last_Process_Time',
        'ao.User_Need_Extract_Reply',
        'ao.User_Process_Status',
        'ao.User_Remark',
        'ao.User_ViewCount',
        // 'ao.Website_No',
    ];
    $query = $db->createQueryBuilder()
        ->select(implode(',', $fields))
        ->from('article_operation', 'ao')
        ->where($condition);

    // join 的表
    $leftJoins = [
        ['ao', 'article_detail', 'ad', 'ao.Article_Detail_ID=ad.Article_Detail_ID'],
    ];
    foreach ($leftJoins as $leftJoin) {
        $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
    }

    // echo $query->getSQL();exit;
    $rst = $query->execute()
        ->fetchAll();

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 获取操作信息耗时: %sms, fetched count: %s", date('Y-m-d H:i:s'), $cost, count($rst)), PHP_EOL;

    return $rst;
}


/**
 * 获取标签信息
 *
 * @param Connection $db
 * @param string $condition
 * @return array
 */
function getTag(Connection $db, $condition)
{
    $_start = microtime(true);
    $fields = [
        'at.Article_Detail_ID',
        't.Article_Count',
        't.Client_ID',
        't.Created_By_User_ID',
        't.Created_Time',
        't.Tag',
        't.Tag_Class_ID',
        't.Tag_ID',
    ];
    $query = $db->createQueryBuilder()
        ->select(implode(',', $fields))
        ->from('article_tag', 'at')
        ->where($condition);

    // join 的表
    $leftJoins = [
        ['at', 'article_detail', 'ad', 'at.Article_Detail_ID=ad.Article_Detail_ID'],
        ['at', 'tag', 't', 'at.Tag_ID=t.Tag_ID'],
    ];
    foreach ($leftJoins as $leftJoin) {
        $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
    }

    // echo $query->getSQL();exit;
    $rst = $query->execute()
        ->fetchAll();

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 获取文章标签耗时: %sms, fetched count: %s", date('Y-m-d H:i:s'), $cost, count($rst)), PHP_EOL;

    return $rst;
}


/**
 * @param Connection $db
 * @param string $condition
 * @return array
 */
function template(Connection $db, $condition)
{
    $_start = microtime(true);
    $fields = [];
    $query = $db->createQueryBuilder()
        ->select(implode(',', $fields))
        ->from('', '')
        ->where($condition);

    // join 的表
    $leftJoins = [
        // ['ad', 'article_number', 'an', 'an.Article_Record_MD5_ID=ad.Record_MD5_ID'],
    ];
    foreach ($leftJoins as $leftJoin) {
        $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
    }

    // echo $query->getSQL();exit;
    $rst = $query->execute()
        ->fetchAll();

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 耗时: %sms, fetched count: %s", date('Y-m-d H:i:s'), $cost, count($rst)), PHP_EOL;

    return $rst;
}



