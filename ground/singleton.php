<?php

/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* singleton.php
* 
* Author: HFX 2018-03-28 17:25
*/

class Singleton
{
    // 2. 静态变量存放该类的实例（一般为私有）
    private static $instance;

    // 1. 私有的构造方法
    private function __construct() {}

    // 4. PHP 特有，可以防止克隆产生新的实例
    private function __clone() {}

    // 3. 一个获取实例的方法
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}


// var_dump(Singleton::getInstance());

$var = Singleton::getInstance();