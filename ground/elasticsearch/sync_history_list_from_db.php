<?php

use Doctrine\DBAL\Connection;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Knowlesys\Database;
use Knowlesys\IndexArticleList;

require_once(__DIR__ . '/../../vendor/autoload.php');

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


    public function getIndexClient()
    {
        require __DIR__ . '/../../vendor/autoload.php';
        return new IndexArticleList();
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
        $interval = 600;

        /**
         * @var $indexClient IndexArticleList
         */
        $indexClient = $this->worker->getIndexClient();

        // 将时间切成段
        for ($startTime; $startTime < $endTime; $startTime += $interval) {
            $indexClient->run($startTime, min($startTime + $interval - 1, $endTime));
        }
    }
}


$pool = new Pool(32, TaskWorker::class);

$pool->submit(new Task('2019-08-01', '2019-08-01 00:00:00', '2019-08-01 11:59:59'));
$pool->submit(new Task('2019-08-02', '2019-08-02 00:00:00', '2019-08-02 23:59:59'));
$pool->submit(new Task('2019-08-03', '2019-08-03 00:00:00', '2019-08-03 23:59:59'));
$pool->submit(new Task('2019-08-04', '2019-08-04 00:00:00', '2019-08-04 23:59:59'));
$pool->submit(new Task('2019-08-05', '2019-08-05 00:00:00', '2019-08-05 23:59:59'));
$pool->submit(new Task('2019-08-06', '2019-08-06 00:00:00', '2019-08-06 23:59:59'));
$pool->submit(new Task('2019-08-07', '2019-08-07 00:00:00', '2019-08-07 23:59:59'));
$pool->submit(new Task('2019-08-08', '2019-08-08 00:00:00', '2019-08-08 23:59:59'));
$pool->submit(new Task('2019-08-09', '2019-08-09 00:00:00', '2019-08-09 23:59:59'));
$pool->submit(new Task('2019-08-10', '2019-08-10 00:00:00', '2019-08-10 23:59:59'));
$pool->submit(new Task('2019-08-11', '2019-08-11 00:00:00', '2019-08-11 23:59:59'));
$pool->submit(new Task('2019-08-12', '2019-08-12 00:00:00', '2019-08-12 23:59:59'));
