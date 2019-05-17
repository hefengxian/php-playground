<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* Database.php
* 
* Author: HFX 2018-03-12 18:11
*/

namespace Knowlesys;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Throwable;


/**
 * Class Database
 *
 * @version 1.0
 */
class Database
{
    static $defaultConfig = [
        'dbname' => 'mymonitor',                // 数据库名
        'user' => 'root',                    // 数据库用户名
        'password' => 'poms@db',                // 数据库密码
        'port' => 3306,                         // 端口号
        'host' => '192.168.1.116',              // 数据库地址
        'driver' => 'pdo_mysql',                // PDO驱动，固定为MySQL，请勿修改
        'charset' => 'UTF8',                    // 数据库使用编码
        'driverOptions' => [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            // \PDO::ATTR_CASE => \PDO::CASE_LOWER
        ]
    ];

    /**
     * @param array|null $config 配置
     * @return Connection
     */
    public static function getConnection($config = null)
    {
        $config = empty($config) ? self::$defaultConfig : $config;
        try {
            $conn = DriverManager::getConnection($config);
        } catch (Throwable $e) {
            $conn = false;
        }
        return $conn;
    }
}