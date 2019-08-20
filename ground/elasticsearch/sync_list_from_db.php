<?php
require_once(__DIR__ . '/../../vendor/autoload.php');

use Knowlesys\IndexArticleList;

$indexInstance = new IndexArticleList();

$curTime = time();
// 过去 2 min
$startTime = $curTime - 90;

$indexInstance->run($startTime, $curTime);