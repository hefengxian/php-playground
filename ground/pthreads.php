<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* pthreads.php
* 
* Author: HFX 2018-06-13 12:54
*/


if (!extension_loaded('pthreads')) {
    echo 'pthreads not load' . PHP_EOL;
}

/*class w extends Thread {
    public function run()
    {
        echo "Hello World {$this->getThreadId()}\n";
    }
}

for ($i = 0; $i < 5; $i++) {
    $thread = new W();
    $thread->start();
}*/

class Database extends Worker
{
    private $num = 0;
    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function getConn()
    {
        require "../vendor/autoload.php";
        return \Knowlesys\Database::getConnection();
    }

    public function getNum()
    {
        /*$this->synchronized(function() {
            $this->num = $this->num + 2;
        }, $this);*/
        $this->num = $this->num + 2;
        return $this->num;
    }
}


class MyThread extends Thread
{

    private $_id;
    private $startTime;
    private $endTime;

    public function __construct($id, $startTime, $endTime)
    {
        $this->_id = $id;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function run()
    {
        /**
         * @var $db \Doctrine\DBAL\Connection
         */
        /*$db = $this->worker->getConn();
        $query = $db->createQueryBuilder();
        $rst = $query->select("version()")
            ->execute()
            ->fetchColumn(0);
        echo sprintf('%s result: %s', $this->_id, $rst), PHP_EOL;*/
        while (true) {
            $num = $this->worker->getNum();
            echo sprintf('线程: %s Number: %s', $this->_id, $num), PHP_EOL;
            sleep(1);
        }

    }
}

$pool = new Pool(4, Database::class);
$pool->submit(new MyThread('001'));
$pool->submit(new MyThread('002'));
$pool->submit(new MyThread('003'));
$pool->submit(new MyThread('004'));

// $worker = new Database();
//
// $worker->stack(new MyThread('001'));
// $worker->stack(new MyThread('002'));
// $worker->stack(new MyThread('003'));
// $worker->stack(new MyThread('004'));
