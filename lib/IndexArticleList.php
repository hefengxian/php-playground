<?php

namespace Knowlesys;

use Doctrine\DBAL\Connection;
use Elasticsearch\ClientBuilder;
use Exception;
use Knowlesys\Database;

error_reporting(E_ERROR);
set_time_limit(0);
ini_set('memory_limit', -1);

// include_once __DIR__ . '/../../vendor/autoload.php';


class IndexArticleList
{

    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DATE_FORMAT = 'Y-m-d';
    const INDEX_PREFIX = 'kwm-list-';


    /**
     * @var Connection
     */
    private $db;
    private $esClient;


    public function __construct()
    {
        $dbConfig = array_merge(Database::$defaultConfig, ['host' => '192.168.1.116']);
        $this->db = Database::getConnection($dbConfig);
        $this->esClient = ClientBuilder::create()
            ->setHosts([
                'http://192.168.1.47:9200',
                'http://192.168.1.48:9200'
            ])
            ->build();
    }


    /**
     * 运行的主方法
     *
     * @param int $startTime
     * @param int $endTime
     * @throws \Doctrine\DBAL\DBALException
     */
    public function run($startTime, $endTime)
    {
        $_currentTaskStart = microtime(true);
        $format = self::DATETIME_FORMAT;

        $condition = sprintf("between '%s' and '%s'", date($format, $startTime), date($format, $endTime));
        echo sprintf('[%s] [start]本次任务查询条件: %s', date($format), $condition), PHP_EOL;

        // 检查采集时间确定索引已经创建
        // 经过实测发现，分类表中的采集时间并不准确
        // $timeRange = $this->getExtractedTimeRange($condition);
        // $this->createIndexByTime($timeRange['min_time'], $timeRange['max_time']);

        // 获取到需要同步的 ID 之后对索引进行修改或者更新
        $articleIDs = $this->getArticleIDNeedToSync($condition);

        $idChunks = array_chunk($articleIDs, 1000);
        foreach ($idChunks as $ids) {
            // 获取文章相关的信息
            $articles = $this->getArticleDetail($ids);
            // 获取分类信息，不使用获取 ID 时的结果，主要是可能有历史分类
            $subjects = $this->getStatSubject($ids);
            // 文章操作信息
            $operations = $this->getOperation($ids);
            // 标签信息
            $tags = $this->getTag($ids);
            // 已经删除的标记信息，与客户对应
            $deletedArticles = $this->getDeleted($ids);

            $_dataProcessStart = microtime(true);
            $params = [];
            $indexNames = [];
            foreach ($articles as &$article) {
                $extractedDate = explode(' ', $article['Extracted_Time'])[0];
                $indexName = self::INDEX_PREFIX . $extractedDate;
                // 用来获取所有的索引名称
                $indexNames[$indexName] = 0;
                $params['body'][] = [
                    'index' => [
                        '_index' => $indexName,
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

                $article['Deleted'] = array_values(array_filter($deletedArticles, function ($deleted) use ($article) {
                    return $article['Article_Detail_ID'] == $deleted['Article_Detail_ID'];
                }));

                $params['body'][] = $article;
            }

            $dataProcessCost = round((microtime(true) - $_dataProcessStart));
            echo sprintf("[%s] 本次处理数据耗时: %ss, 共处理: %s 篇文章", date($format), $dataProcessCost, count($articles)), PHP_EOL;

            // 索引数据之前，检查索引是否存在，不存在要创建一个
            $this->createIndexByNames(array_keys($indexNames));

            try {
                $indexResponse = $this->esClient->bulk($params);
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
        }

        $cost = round((microtime(true) - $_currentTaskStart));
        echo sprintf("[%s] [end]本次查询任务总耗时: %ss, 共处理: %s 篇文章", date($format), $cost, count($articleIDs)), PHP_EOL, PHP_EOL;
    }


    /**
     * @param string $condition
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    function getArticleIDNeedToSync($condition)
    {
        $_start = microtime(true);
        $db = $this->db;
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
     * @param array $condition 条件
     * @return array
     */
    function getArticleDetail($condition)
    {
        $_start = microtime(true);
        $db = $this->db;
        $format = self::DATETIME_FORMAT;
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
     * @param array $articleIDs
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    function getStatSubject($articleIDs)
    {
        $_start = microtime(true);
        $format = self::DATETIME_FORMAT;
        $db = $this->db;
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
     * @param array $articleIDs
     * @return array
     */
    function getOperation($articleIDs)
    {
        $_start = microtime(true);
        $format = self::DATETIME_FORMAT;
        $db = $this->db;
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
     * @param array $articleIDs
     * @return array
     */
    function getTag($articleIDs)
    {
        $_start = microtime(true);
        $format = self::DATETIME_FORMAT;
        $db = $this->db;
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
     * 获取删除文章的信息
     *
     * @param array $articleIDs
     * @return array
     */
    function getDeleted($articleIDs)
    {
        $_start = microtime(true);
        $format = self::DATETIME_FORMAT;
        $db = $this->db;
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


    function getExtractedTimeRange($condition)
    {
        $_start = microtime(true);
        $db = $this->db;
        $format = self::DATETIME_FORMAT;
        $sqlParts = [];
        for ($i = 0; $i < 100; $i++) {
            $tableName = 'stat_article_subject_' . $i;
            $query = $db->createQueryBuilder()
                ->select('Article_Extracted_Time')
                ->from($tableName)
                ->where("Created_Time {$condition}");

            $sqlParts[] = $query->getSQL();
        }

        $union = implode(' UNION ALL ', $sqlParts);
        $sql = "select min(Article_Extracted_Time) as min_time, max(Article_Extracted_Time) as max_time from ({$union}) t";
        $rst = $db->query($sql)
            ->fetchAll();

        $cost = round((microtime(true) - $_start) * 1000);
        echo sprintf("[%s] 获取采集时间范围耗时: %sms, fetched count: %s, unique id count: %s", date($format), $cost, count($rst), count($result)), PHP_EOL;

        return $rst[0];
    }


    /**
     * 通过一系列索引的名称创建索引
     *
     * @param array $names 名称数组
     */
    public function createIndexByNames(array $names)
    {
        $createIndexJSON = file_get_contents(__DIR__ . '/kwm-list-mapping.json');
        foreach ($names as $indexName) {
            // 检查是否存在
            $exists = $this->esClient->indices()->exists(['index' => $indexName]);
            if (!$exists) {
                $params = [
                    'index' => $indexName,
                    'body' => json_decode($createIndexJSON, true)
                ];
                $this->esClient->indices()->create($params);
            }
        }
    }


    /**
     * 通过一个时间范围，按天创建一定规则的索引
     *
     * @param string $startDatetime 开始时间
     * @param string $endDatetime 结束时间
     */
    public function createIndexByTime($startDatetime, $endDatetime)
    {
        $start = strtotime(explode(' ', $startDatetime)[0]);
        $end = strtotime(explode(' ', $endDatetime)[0]);

        $names = [];
        for ($start; $start <= $end; $start += (60 * 60 * 24)) {
            $names[] = self::INDEX_PREFIX . date(self::DATE_FORMAT, $start);
        }
        $this->createIndexByNames($names);
    }
}