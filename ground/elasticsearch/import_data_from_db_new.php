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

include_once __DIR__ . '/../../vendor/autoload.php';

error_reporting(E_ERROR);
set_time_limit(0);
ini_set('memory_limit', -1);


$startTimeFile = __DIR__ . '/start_time_new.txt';
$startTimeString = @file_get_contents($startTimeFile);

if ($startTimeString === false) {
    echo "没有发现 `$startTimeFile` 文件" . PHP_EOL;
    exit;
}

$format = 'Y-m-d H:i:s';
$interval = 3600;
$startTime = strtotime($startTimeString);
$stopTime = strtotime('2019-08-18 23:59:59');
// $stopTime = time() - (3 * 60);
if ($startTime >= $stopTime) {
    // 时间不够，等待下一次执行
    exit;
}
// 将当前截止时间写入到文件
// @file_put_contents($startTimeFile, date($format, $stopTime));

$dbConfig = array_merge(Database::$defaultConfig, ['host' => '192.168.1.116']);
$db = Database::getConnection($dbConfig);
$esClient = ClientBuilder::create()
    ->setHosts([
        'http://192.168.1.47:9200',
        'http://192.168.1.48:9200'
    ])
    ->build();

$createIndexJSON = <<<EOF
{
  "settings": {
    "analysis": {
      "analyzer": {
        "cn_analyzer": {
          "type": "custom",
          "tokenizer": "ik_max_word",
          "char_filter": [
            "html_strip"
          ],
          "filter": [
            "lowercase"
          ]
        }
      }
    }
  },
  "mappings": {
    "properties": {
      "Article_Detail_ID": {
        "type": "long"
      },
      "Website_No": {
        "type": "keyword"
      },
      "Media_Type_Code": {
        "type": "keyword"
      },
      "Article_URL_MD5_ID": {
        "type": "keyword"
      },
      "Domain_Code": {
        "type": "keyword"
      },
      "Article_Title": {
        "type": "text",
        "analyzer": "cn_analyzer",
        "search_analyzer": "ik_smart",
        "fielddata": true
      },
      "Article_Abstract": {
        "type": "text",
        "analyzer": "cn_analyzer",
        "search_analyzer": "ik_smart",
        "fielddata": true
      },
      "Record_MD5_ID": {
        "type": "keyword"
      },
      "Article_Title_FingerPrint": {
        "type": "keyword"
      },
      "Article_Abstract_FingerPrint": {
        "type": "keyword"
      },
      "Microblog_Type": {
        "type": "keyword"
      },
      "Article_PubTime": {
        "type": "date",
        "format": "yyyy-MM-dd HH:mm:ss"
      },
      "Language_Code": {
        "type": "keyword"
      },
      "Extracted_Time": {
        "type": "date",
        "format": "yyyy-MM-dd HH:mm:ss"
      },
      "Article_Content": {
        "type": "text",
        "analyzer": "cn_analyzer",
        "search_analyzer": "ik_smart",
        "fielddata": true
      },
      "Article_Content_FingerPrint": {
        "type": "keyword"
      },
      "City_Area_Code": {
        "type": "keyword"
      },
      "Country_Code": {
        "type": "keyword"
      },
      "District_Area_Code": {
        "type": "keyword"
      },
      "Province_Area_Code": {
        "type": "keyword"
      },
      "Subject_Stat": {
        "type": "nested",
        "properties": {
          "Article_Detail_ID": {
            "type": "long"
          },
          "Client_ID": {
            "type": "long"
          },
          "Created_Time": {
            "type": "date",
            "format": "yyyy-MM-dd HH:mm:ss"
          },
          "Emotion_Type": {
            "type": "byte"
          },
          "Is_Valid": {
            "type": "boolean"
          },
          "Junk_Score": {
            "type": "short"
          },
          "Relative_Score": {
            "type": "short"
          },
          "Sentiment_Score": {
            "type": "short"
          },
          "Similar_Record_Oldest_ID": {
            "type": "long"
          },
          "Subject_ID": {
            "type": "long"
          },
          "Total_Score": {
            "type": "short"
          }
        }
      },
      "Operation": {
        "type": "nested",
        "properties": {
          "Article_Detail_ID": {
            "type": "long"
          },
          "Client_ID": {
            "type": "long"
          },
          "Followup_Status": {
            "type": "keyword"
          },
          "User_Confirm_Emotion_Type": {
            "type": "byte"
          },
          "User_Last_Process_Time": {
            "type": "date",
            "format": "yyyy-MM-dd HH:mm:ss"
          },
          "User_Process_Status": {
            "type": "keyword"
          }
        }
      },
      "Tag": {
        "type": "nested",
        "properties": {
          "Article_Detail_ID": {
            "type": "long"
          },
          "Client_ID": {
            "type": "long"
          },
          "Tag_ID": {
            "type": "long"
          }
        }
      },
      "Deleted": {
        "type": "nested",
        "properties": {
          "Article_Detail_ID": {
            "type": "long"
          },
          "Client_ID": {
            "type": "long"
          },
          "Article_Deleted_ID": {
            "type": "long"
          }
        }
      }
    }
  }
}
EOF;


while ($startTime < $stopTime) {
    $_currentTaskStart = microtime(true);
    $condition = sprintf("between '%s' and '%s'", date($format, $startTime), date($format, ($startTime + $interval - 1)));
    echo sprintf('[%s] [start]本次任务查询条件: %s', date($format), $condition), PHP_EOL;

    // 获取分类文章信息
    // $subjects = getStatSubject($db, $condition);

    // 获取到需要同步的 ID 之后对索引进行修改或者更新
    $articleIDs = getArticleIDNeedToSync($db, $condition);
    // $idCount = count($articleIDs);
    $idChunks = array_chunk($articleIDs, 10000);
    $params = [];
    foreach ($idChunks as $ids) {
        // 获取文章相关的信息
        $articles = getArticleDetail($db, $ids);
        // 获取分类信息，不使用获取 ID 时的结果，主要是可能有历史分类
        $subjects = getStatSubject($db, $ids);
        // 文章操作信息
        $operations = getOperation($db, $ids);
        // 标签信息
        $tags = getTag($db, $ids);
        // 已经删除的标记信息，与客户对应
        $deletedArticles = getDeleted($db, $ids);


        $_dataProcessStart = microtime(true);
        foreach ($articles as &$article) {
            $params['body'][] = [
                'index' => [
                    '_index' => 'kwm-list',
                    '_id' => $article['Article_Detail_ID'],
                ]
            ];

            // TODO 否则会被修改 Mapping
            // 数字类型要转换
            // NULL 要转换

            $article['Operation'] = [];
            foreach ($operations as $oKey => $operation) {
                if ($article['Article_Detail_ID'] == $operation['Article_Detail_ID']) {
                    // 转 Boolean
                    $article['Operation'][] = $operation;
                    unset($operations[$oKey]);
                }
            }

            $article['Subject_Stat'] = [];
            foreach ($subjects as $sKey => $subject) {
                if ($article['Article_Detail_ID'] == $subject['Article_Detail_ID']) {
                    // 转 Boolean
                    $subject['Is_Valid'] = $subject['Is_Valid'] > 0;
                    $article['Subject_Stat'][] = $subject;
                    unset($subjects[$sKey]);
                }
            }

            $article['Tag'] = array_values(array_filter($tags, function ($tag) use ($article) {
                return $article['Article_Detail_ID'] == $tag['Article_Detail_ID'];
            }));

            $params['body'][] = $article;
        }

        $dataProcessCost = round((microtime(true) - $_dataProcessStart) * 1000);
        echo sprintf("[%s] 本次处理数据耗时: %sms, 共处理: %s 篇文章", date($format), $dataProcessCost, count($articles)), PHP_EOL;
    }

    try {
        $indexResponse = $esClient->bulk($params);
    } catch (Exception $e) {
        var_dump($e->getMessage());
        exit;
    }

    // 统计索引情况2019-05-13 23:12:00
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
    echo sprintf("[%s] 索引结果统计 %s", date($format), implode(', ', $statisticParts)), PHP_EOL;

    $startTime += $interval;
    $cost = round((microtime(true) - $_currentTaskStart));
    echo sprintf("[%s] [end]本次查询任务总耗时: %ss, 共处理: %s 篇文章", date($format), $cost, count($articleIDs)), PHP_EOL, PHP_EOL;
}


function getArticleIDNeedToSync(Connection $db, $condition)
{
    $_start = microtime(true);
    $format = 'Y-m-d H:i:s';
    $sqlParts = [];
    for ($i = 0; $i < 100; $i++) {
        $tableName = 'stat_article_subject_' . $i;
        $query = $db->createQueryBuilder()
            ->select('Article_Detail_ID')
            ->from($tableName)
            ->where("Created_Time {$condition}");

        $sqlParts[] = $query->getSQL();
    }
    $sql = implode(' UNION ALL ', $sqlParts);
    $rst = $db->query($sql)
        ->fetchAll();

    $rst = array_map(function ($v) {
        return $v['Article_Detail_ID'];
    }, $rst);
    $result = array_unique($rst);

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 获取需要同步的文章 ID 耗时: %sms, fetched count: %s, unique id count: %s", date($format), $cost, count($rst), count($result)), PHP_EOL;

    return $result;
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
    $format = 'Y-m-d H:i:s';
    $fields = [
        'IFNULL(ac.Article_Abstract, ad.Article_Abstract)       AS Article_Abstract',
        'ad.Article_Abstract_FingerPrint',
        // 'IFNULL(ac.Article_Author, ad.Article_Author)         AS Article_Author',
        'ad.Article_Detail_ID',
        'IFNULL(ac.Article_PubTime, ad.Article_PubTime)         AS Article_PubTime',
        // 'IFNULL(ac.Article_PubTime_Str, ad.Article_PubTime_Str) AS Article_PubTime_Str',
        // 'ad.Article_Search_Keywords',
        // 'IFNULL(ac.Article_Source, ad.Article_Source)         AS Article_Source',
        'IFNULL(ac.Article_Title, ad.Article_Title)             AS Article_Title',
        'ad.Article_Title_FingerPrint',
        // 'ad.Article_URL',
        'ad.Article_URL_MD5_ID',
        // 'IFNULL(ac.Author_Raw_ID, ad.Author_Raw_ID)         AS Author_Raw_ID',
        'ad.Domain_Code',
        'ad.Extracted_Time',
        // 'ad.Is_With_Content',
        'ad.Language_Code',
        'ad.Media_Type_Code',
        'ad.Microblog_Type',
        // 'ad.Node_ID',
        // 'ad.Order_No_In_Search_Result',
        'ad.Record_MD5_ID',
        // 'ad.RefPage_Type',
        // 'ad.RefPage_URL_ID',
        // 'ad.Transferred_Time',
        'ad.Website_No',

        // from article_content
        'ac.Article_Content',
        'ac.Article_Content_FingerPrint',
        // 'ac.Article_Content_ID',
        // 'ac.Is_Extract_After_Detail',
        // 'ac.Is_Html_Content',

        // from article_number
        // 'an.Article_Number_ID',
        // 'an.Down_Count',
        // 'an.Forward_Count',
        // 'an.Last_Update_Time',
        // 'an.Like_Count',
        // 'an.Reply_Count',
        // 'an.Up_Count',
        // 'an.View_Count',

        // from article_mention_area
        // 'ama.Article_Mention_Area_ID',
        'ama.City_Area_Code',
        'ama.Country_Code',
        'ama.District_Area_Code',
        'ama.Province_Area_Code',
    ];


    $query = $db->createQueryBuilder();
    $query->select(implode(',', $fields))
        ->from('article_detail', 'ad')
        ->where($query->expr()->in('ad.Article_Detail_ID', $condition));

    $leftJoins = [
        // ['ad', 'article_number', 'an', 'an.Article_Record_MD5_ID=ad.Record_MD5_ID'],
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

    $rst = $query->execute()
        ->fetchAll();
    $cost = round((microtime(true) - $_start) * 1000);

    echo sprintf("[%s] 获取文章基础信息耗时: %sms, fetched count: %s", date($format), $cost, count($rst)), PHP_EOL;
    return $rst;
}


/**
 * 获取分类信息
 *
 * @param Connection $db
 * @param array $articleIDs
 * @return array
 * @throws \Doctrine\DBAL\DBALException
 */
function getStatSubject(Connection $db, $articleIDs)
{
    $_start = microtime(true);
    $format = 'Y-m-d H:i:s';
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
        'sas.Subject_ID',
        'sas.Total_Score',
    ];
    $result = [];

    // 去 100 个分类表中查询数据
    $sqlParts = [];
    for ($i = 0; $i < 100; $i++) {
        $tableName = 'stat_article_subject_' . $i;

        // echo sprintf('当前获取 %s', $tableName), PHP_EOL;

        $query = $db->createQueryBuilder();
        $query->select(implode(',', $fields))
            ->from($tableName, 'sas')
            ->where($query->expr()->in("Article_Detail_ID", $articleIDs));

        $sqlParts[] = $query->getSQL();
        /*$rst = $query->execute()
            ->fetchAll();

        foreach ($rst as $item) {
            $result[] = $item;
        }*/
    }
    $sql = implode(' UNION ALL ', $sqlParts);
    // echo $sql, PHP_EOL;

    $result = $db->query($sql)
        ->fetchAll();

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 获取文章所有分类信息耗时: %sms, fetched count: %s", date($format), $cost, count($result)), PHP_EOL;

    return $result;
}


/**
 * 获取操作信息
 *
 * @param Connection $db
 * @param array $articleIDs
 * @return array
 */
function getOperation(Connection $db, $articleIDs)
{
    $_start = microtime(true);
    $format = 'Y-m-d H:i:s';
    $fields = [
        'ao.Article_Detail_ID',
        'ao.Client_ID',
        'ao.Followup_Status',
        'ao.User_Confirm_Emotion_Type',
        'ao.User_Last_Process_Time',
        'ao.User_Process_Status',
    ];
    $query = $db->createQueryBuilder();

    $query->select(implode(',', $fields))
        ->from('article_operation', 'ao')
        ->where($query->expr()->in("Article_Detail_ID", $articleIDs));

    // join 的表
    $leftJoins = [
        // ['ao', 'article_detail', 'ad', 'ao.Article_Detail_ID=ad.Article_Detail_ID'],
    ];
    foreach ($leftJoins as $leftJoin) {
        $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
    }

    // echo $query->getSQL();exit;
    $rst = $query->execute()
        ->fetchAll();

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 获取操作信息耗时: %sms, fetched count: %s", date($format), $cost, count($rst)), PHP_EOL;

    return $rst;
}


/**
 * 获取标签信息
 *
 * @param Connection $db
 * @param array $articleIDs
 * @return array
 */
function getTag(Connection $db, $articleIDs)
{
    $_start = microtime(true);
    $format = 'Y-m-d H:i:s';
    $fields = [
        'Article_Detail_ID',
        'Tag_ID',
    ];
    $query = $db->createQueryBuilder();
    $query->select(implode(',', $fields))
        ->from('article_tag')
        ->where($query->expr()->in('Article_Detail_ID', $articleIDs));

    // join 的表
    $leftJoins = [
        // ['at', 'article_detail', 'ad', 'at.Article_Detail_ID=ad.Article_Detail_ID'],
        // ['at', 'tag', 't', 'at.Tag_ID=t.Tag_ID'],
    ];
    foreach ($leftJoins as $leftJoin) {
        $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
    }

    // echo $query->getSQL();exit;
    $rst = $query->execute()
        ->fetchAll();

    $cost = round((microtime(true) - $_start) * 1000);
    echo sprintf("[%s] 获取文章标签耗时: %sms, fetched count: %s", date($format), $cost, count($rst)), PHP_EOL;

    return $rst;
}


/**
 * @param Connection $db
 * @param array $articleIDs
 * @return array
 */
function getDeleted(Connection $db, $articleIDs)
{
    $_start = microtime(true);
    $format = 'Y-m-d H:i:s';
    $fields = [
        'Article_Detail_ID',
        'Client_ID',
        'Article_Deleted_ID',
    ];
    $query = $db->createQueryBuilder();
    $query->select(implode(',', $fields))
        ->from('article_deleted')
        ->where($query->expr()->in('Article_Detail_ID', $articleIDs));

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
    echo sprintf("[%s] 获取删除文章信息耗时: %sms, fetched count: %s", date($format), $cost, count($rst)), PHP_EOL;

    return $rst;
}


/**
 * @param Connection $db
 * @param array $condition
 * @return array
 */
function template(Connection $db, $condition)
{
    $_start = microtime(true);
    $format = 'Y-m-d H:i:s';
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
    echo sprintf("[%s] 耗时: %sms, fetched count: %s", date($format), $cost, count($rst)), PHP_EOL;

    return $rst;
}



