<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* php_conn_mysql8.php
* 
* Author: HFX 2018-05-30 12:15
*/

// MySQL 需要设置 [mysqld]
// default_authentication_plugin=mysql_native_password
// $pdo = new PDO('mysql:host=192.168.1.47;dbname=tzm', 'tzm', 'tzm@admin');
$pdo = new PDO('mysql:host=192.168.1.119;dbname=mymonitor', 'temp', 'temp@admin');
$result = $pdo->query('select count(*) from node_monitor_os')
    ->fetchAll(PDO::FETCH_ASSOC);
var_dump($result);