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
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Knowlesys\Database;

include_once __DIR__ . '/../../vendor/autoload.php';

error_reporting(E_ERROR);
set_time_limit(0);
ini_set('memory_limit', -1);


class TaskWorker extends Worker
{
    public function run()
    {
        // 要写到 Worker 里
        include_once __DIR__ . '/../../vendor/autoload.php';
    }

    /**
     * 获取数据库链接
     *
     * @return Connection
     */
    public function getDB()
    {
        require  __DIR__ . '/../../vendor/autoload.php';
        $index = rand(0, 1);
        $hosts = [
          '192.168.1.116',
          '192.168.1.119',
        ];
        $dbConfig = array_merge(Database::$defaultConfig, ['host' => $hosts[$index]]);
        print_r($dbConfig);
        $db = Database::getConnection($dbConfig);
        return $db;
    }


    /**
     * 获取 ES 客户端
     *
     * @return Client
     */
    public function getESClient()
    {
        require __DIR__ . '/../../vendor/autoload.php';
        $esClient = ClientBuilder::create()
            ->setHosts([
                'http://192.168.1.47:9200',
                'http://192.168.1.48:9200'
            ])
            ->build();
        return $esClient;
    }
}


class Task extends Thread
{
    /**
     * @var string 线程名称
     */
    private $name;

    /**
     * @var string 开始时间
     */
    private $startTime;

    /**
     * @var string 结束时间
     */
    private $endTime;


    const FORMAT = 'Y-m-d H:i:s';


    /**
     * Task constructor.
     *
     * @param string $name 线程名称
     * @param string $startTime 开始时间
     * @param string $endTime 结束时间
     */
    public function __construct($name, $startTime, $endTime)
    {
        $this->name = $name;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }


    public function run()
    {
        $startTime = strtotime($this->startTime);
        $endTime = strtotime($this->endTime);

        // 检查时间，必须在同一天；为了分索引
        if (date('Y-m-d', $startTime) != date('Y-m-d', $endTime)) {
            echo sprintf('ERROR: 时间不在同一天，将无法正确存入索引；时间：%s, %s', $this->startTime, $this->endTime), PHP_EOL;
            exit;
        }

        $format = self::FORMAT;
        $db = $this->worker->getDB();
        /**
         * @var $esClient Client
         */
        $esClient = $this->worker->getESClient();

        // 获取索引名字
        $indexName = sprintf('ks-article-%s', date('Ymd', $startTime));
        $this->generateIndex($esClient, $indexName);
        $timeResponse = $esClient->search([
            'index' => $indexName,
            'body' => [
                '_source' => [
                    'Extracted_Time'
                ],
                'query' => [
                    'range' => [
                        'Extracted_Time' => [
                            'gte' => $this->startTime,
                            'lte' => $this->endTime,
                        ]
                    ]
                ],
                'sort' => [
                    'Extracted_Time' => [
                        'order' => 'desc'
                    ]
                ],
                'size' => 1,
            ],
        ]);
        // print_r($timeResponse['hits']);
        $hits = $timeResponse['hits']['hits'];
        if (count($hits) > 0) {
            $startTime = $hits[0]['_source']['Extracted_Time'];
            $startTime = strtotime($startTime);
        }
        echo sprintf('task: %s, start time: %s', $this->name, date($format, $startTime)), PHP_EOL;
        // exit;

        while ($startTime < $endTime) {
            $_currentTaskStart = microtime(true);
            $condition = sprintf("between '%s' and '%s'", date($format, $startTime), date($format, $startTime + 59));
            echo sprintf('[%s]%s [start]本次任务查询条件: %s', date($format), $this->name, $condition), PHP_EOL;

            $params = $this->generateParams($db, $condition, $indexName);
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
            echo sprintf("[%s]%s 索引结果统计 %s", date($format), $this->name, implode(', ', $statisticParts)), PHP_EOL;

            $startTime += 60;
            $cost = round((microtime(true) - $_currentTaskStart) * 1000);
            echo sprintf("[%s]%s [end]本次查询任务总耗时: %sms", date($format), $this->name, $cost), PHP_EOL, PHP_EOL;
        }
    }


    /**
     * 创建索引
     *
     * @param Client $client
     * @param string $indexName
     */
    function generateIndex($client, $indexName)
    {
        $exists = $client->indices()->exists(['index' => $indexName]);
        if (!$exists) {
            // create
            $indexJson = <<<EOF
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
      "Article_Abstract": {
        "type": "text",
        "analyzer": "cn_analyzer",
        "search_analyzer": "ik_smart",
        "fielddata": true
      },
      "Article_Abstract_FingerPrint": {
        "type": "keyword"
      },
      "Article_Author": {
        "type": "text",
        "analyzer": "ik_smart",
        "search_analyzer": "ik_smart",
        "fields": {
          "raw": {
            "type": "keyword"
          }
        }
      },
      "Article_Detail_ID": {
        "type": "long"
      },
      "Article_PubTime": {
        "type": "date",
        "format": "yyyy-MM-dd HH:mm:ss"
      },
      "Article_PubTime_Str": {
        "type": "keyword"
      },
      "Article_Search_Keywords": {
        "type": "keyword"
      },
      "Article_Source": {
        "type": "keyword"
      },
      "Article_Title": {
        "type": "text",
        "analyzer": "cn_analyzer",
        "search_analyzer": "ik_smart",
        "fielddata": true
      },
      "Article_Title_FingerPrint": {
        "type": "keyword"
      },
      "Article_URL": {
        "type": "keyword"
      },
      "Article_URL_MD5_ID": {
        "type": "keyword"
      },
      "Author_Raw_ID": {
        "type": "keyword"
      },
      "Domain_Code": {
        "type": "keyword"
      },
      "Extracted_Time": {
        "type": "date",
        "format": "yyyy-MM-dd HH:mm:ss"
      },
      "Is_With_Content": {
        "type": "boolean"
      },
      "Language_Code": {
        "type": "keyword"
      },
      "Media_Type_Code": {
        "type": "keyword"
      },
      "Microblog_Type": {
        "type": "keyword"
      },
      "Node_ID": {
        "type": "short"
      },
      "Order_No_In_Search_Result": {
        "type": "integer"
      },
      "Record_MD5_ID": {
        "type": "keyword"
      },
      "RefPage_Type": {
        "type": "keyword"
      },
      "RefPage_URL_ID": {
        "type": "long"
      },
      "Transferred_Time": {
        "type": "date",
        "format": "yyyy-MM-dd HH:mm:ss"
      },
      "Website_No": {
        "type": "keyword"
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
      "Article_Content_ID": {
        "type": "long"
      },
      "Is_Extract_After_Detail": {
        "type": "boolean"
      },
      "Is_Html_Content": {
        "type": "boolean"
      },
      "Article_Number": {
        "properties": {
          "Article_Number_ID": {
            "type": "long"
          },
          "Down_Count": {
            "type": "integer"
          },
          "Forward_Count": {
            "type": "integer"
          },
          "Last_Update_Time": {
            "type": "integer"
          },
          "Like_Count": {
            "type": "integer"
          },
          "Reply_Count": {
            "type": "integer"
          },
          "Up_Count": {
            "type": "integer"
          },
          "View_Count": {
            "type": "integer"
          }
        }
      },
      "Mention_Area": {
        "properties": {
          "Article_Mention_Area_ID": {
            "type": "long"
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
          }
        }
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
          "Source_Type": {
            "type": "keyword"
          },
          "Stat_Article_Subject_ID": {
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
          "Content_Class_ID": {
            "type": "long"
          },
          "Followup_Remark": {
            "type": "keyword"
          },
          "Followup_Status": {
            "type": "keyword"
          },
          "Upload_Status": {
            "type": "keyword"
          },
          "User_Audit_Result": {
            "type": "keyword"
          },
          "User_Confirm_Emotion_Type": {
            "type": "byte"
          },
          "User_ID": {
            "type": "long"
          },
          "User_Last_Process_Time": {
            "type": "date",
            "format": "yyyy-MM-dd HH:mm:ss"
          },
          "User_Need_Extract_Reply": {
            "type": "boolean"
          },
          "User_Process_Status": {
            "type": "keyword"
          },
          "User_Remark": {
            "type": "keyword"
          },
          "User_ViewCount": {
            "type": "integer"
          }
        }
      },
      "Tag": {
        "type": "nested",
        "properties": {
          "Article_Detail_ID": {
            "type": "long"
          },
          "Article_Count": {
            "type": "integer"
          },
          "Client_ID": {
            "type": "long"
          },
          "Created_By_User_ID": {
            "type": "long"
          },
          "Created_Time": {
            "type": "date",
            "format": "yyyy-MM-dd HH:mm:ss"
          },
          "Tag": {
            "type": "keyword"
          },
          "Tag_Class_ID": {
            "type": "long"
          },
          "Tag_ID": {
            "type": "long"
          }
        }
      }
    }
  }
}
EOF;
            $params = [
                'index' => $indexName,
                'body' => json_decode($indexJson, true)
            ];

            // TODO 异常处理
            $client->indices()->create($params);
        }
    }


    /**
     * 生成参数
     *
     * @param Connection $db 数据库链接
     * @param string $condition 条件
     * @param string $indexName 索引名称
     * @return array
     */
    function generateParams($db, $condition, $indexName)
    {
        // 文章基本信息
        $articles = $this->getArticleDetail($db, $condition);
        // file_put_contents('/home/hfx/Desktop/Temp/es_sample.json', json_encode($articles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 文章操作信息
        $operations = $this->getOperation($db, $condition);
        // 分类信息
        $subjects = $this->getStatSubject($db, $condition);
        // 标签信息
        $tags = $this->getTag($db, $condition);
        // 映射信息
        $_dataProcessStart = microtime(true);
        $params = [];
        foreach ($articles as &$article) {
            $params['body'][] = [
                'index' => [
                    '_index' => $indexName,
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
            foreach ($operations as $oKey => $operation) {
                if ($article['Article_Detail_ID'] == $operation['Article_Detail_ID']) {
                    // 转 Boolean
                    $operation['User_Need_Extract_Reply'] = $operation['User_Need_Extract_Reply'] > 0;
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
        echo sprintf("[%s]%s 本次处理数据耗时: %sms, 共处理: %s 篇文章", date(self::FORMAT), $this->name, $dataProcessCost, count($articles)), PHP_EOL;

        return $params;
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
            ->where("ad.Extracted_Time {$condition}");

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

        echo sprintf("[%s]%s 获取文章基础信息耗时: %sms, fetched count: %s", date($format), $this->name, $cost, count($rst)), PHP_EOL;
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
                ->where("Article_Extracted_Time {$condition}");

            // join 的表
            $leftJoins = [
                // ['sas', 'article_detail', 'ad', 'sas.Article_Detail_ID=ad.Article_Detail_ID'],
            ];
            foreach ($leftJoins as $leftJoin) {
                $query->leftJoin($leftJoin[0], $leftJoin[1], $leftJoin[2], $leftJoin[3]);
            }

            $rst = $query->execute()
                ->fetchAll();

            foreach ($rst as $item) {
                $result[] = $item;
            }
        }

        $cost = round((microtime(true) - $_start) * 1000);
        echo sprintf("[%s]%s 获取文章所有分类信息耗时: %sms, fetched count: %s", date($format), $this->name, $cost, count($result)), PHP_EOL;

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
        $format = 'Y-m-d H:i:s';
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
            ->where("Article_Extracted_Time {$condition}");

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
        echo sprintf("[%s]%s 获取操作信息耗时: %sms, fetched count: %s", date($format), $this->name, $cost, count($rst)), PHP_EOL;

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
        $format = 'Y-m-d H:i:s';
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
            ->where("ad.Extracted_Time {$condition}");

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
        echo sprintf("[%s]%s 获取文章标签耗时: %sms, fetched count: %s", date($format), $this->name, $cost, count($rst)), PHP_EOL;

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
        echo sprintf("[%s]%s 耗时: %sms, fetched count: %s", date($format), $this->name, $cost, count($rst)), PHP_EOL;

        return $rst;
    }
}


$pool = new Pool(32, TaskWorker::class);

$pool->submit(new Task('2019-05-20AM', '2019-05-20 00:00:00', '2019-05-20 11:59:59'));
$pool->submit(new Task('2019-05-20PM', '2019-05-20 12:00:00', '2019-05-20 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-21', '2019-05-21 00:00:00', '2019-05-21 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-22', '2019-05-22 00:00:00', '2019-05-22 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-23', '2019-05-23 00:00:00', '2019-05-23 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-24', '2019-05-24 00:00:00', '2019-05-24 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-25', '2019-05-25 00:00:00', '2019-05-25 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-26', '2019-05-26 00:00:00', '2019-05-26 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-27', '2019-05-27 00:00:00', '2019-05-27 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-28', '2019-05-28 00:00:00', '2019-05-28 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-29', '2019-05-29 00:00:00', '2019-05-29 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-30', '2019-05-30 00:00:00', '2019-05-30 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-05-31', '2019-05-31 00:00:00', '2019-05-31 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-06-01', '2019-06-01 00:00:00', '2019-06-01 23:59:59'));
sleep(5);
$pool->submit(new Task('2019-06-02', '2019-06-02 00:00:00', '2019-06-02 23:59:59'));

// while ($pool->collect());
// $pool->shutdown();