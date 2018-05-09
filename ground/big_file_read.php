<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* big_file_read.php
* 
* Author: HFX 2018-03-12 18:01
*/

use Knowlesys\Database;

require('../vendor/autoload.php');

// 文件路径
$filePath = '/home/hfx/Desktop/my_project/民生银行售前支持/bdTrends/';
$resultFilename = $filePath . 'total';

/**
 * 将分割文件合成
 *
 * @param string $filePath 多个分割文件的路径
 * @param string $resultFilename 合成文件的完整文件名
 */
function combineFiles($filePath, $resultFilename)
{
    // 删除已经存在的总结果文件
    unlink($resultFilename);

    $filename = 'xaa';
    $fileCount = 1;
    $breakLinePrefix = '';

    $totalLineCount = 0;
    while ($filename <= 'xal') {
        $handle = fopen($filePath . $filename, 'r');
        while (($line = fgets($handle)) !== false) {
            if (startsWith($line, '{') && !endsWith($line, PHP_EOL)) {
                $breakLinePrefix = $line;
                continue;
            }
            if (!startsWith($line, '{') && endsWith($line, PHP_EOL)) {
                $breakLineSuffix = $line;
                // 写入完整一行
                file_put_contents($resultFilename, $breakLinePrefix . $breakLineSuffix, FILE_APPEND);
                $breakLinePrefix = '';
                $totalLineCount++;
                echo sprintf('Fix break line at: %s' . PHP_EOL, $totalLineCount);
                continue;
            }
            // 追加到新行到文件
            $totalLineCount++;
            file_put_contents($resultFilename, $line, FILE_APPEND);
        }
        $filename++;
        $fileCount++;
    }
    echo sprintf('Combine %s files into %s, total line: %s' . PHP_EOL, $fileCount, $resultFilename, $totalLineCount);
}


/**
 * 判断某个字符串是否以某个字符开始
 *
 * @param string $haystack 要检查的目标字符串
 * @param string $needle 开始字符串
 * @return bool
 */
function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}


/**
 * 判断某个字符串是否以某个字符串结尾
 *
 * @param string $haystack 要检查的目标字符串
 * @param string $needle 结尾字符串
 * @return bool
 */
function endsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length === 0 ||
        (substr($haystack, -$length) === $needle);
}


/**
 * 读取、解析合成的大文件
 *
 * @param string $resultFilename
 */
function readResultFile($resultFilename)
{
    $brokenLines = 0;
    $totalLines = 0;
    $handle = fopen($resultFilename, 'r');

    $keywords = [];
    $companies = [];

    $availableMessages = [];

    while (($line = fgets($handle)) !== false) {
        $msg = json_decode($line, true);
        $totalLines++;
        echo sprintf("Now process: \t %s" . PHP_EOL, number_format($totalLines));
        if ($msg === NULL) {
            sprintf('Line was broken, msg: %s' . PHP_EOL, $line);
            $brokenLines++;
            continue;
        }
        $availableMessages[md5(trim($msg['msg_text']))] = $msg;
        // 插入数据库
        // insert2DB($msg);
        // insert2SQLiteDB($msg);

        $keywords[] = $msg['keywords'];
        $companies[] = $msg['company_name'];
    }
    echo sprintf('Total lines: %s, broken lines: %s' . PHP_EOL, number_format($totalLines), number_format($brokenLines));

    $keywords = array_unique($keywords);
    $companies = array_unique($companies);

    echo sprintf('Unique messages: %s, Unique keywords: %s, Unique companies: %s' . PHP_EOL, number_format(count($availableMessages)), number_format(count($keywords)), number_format(count($companies)));
}


/**
 * 插入到数据库
 *
 * @param array $msg 一条报文消息
 * @return bool|string 插入成功返回 TRUE 失败返回失败原因
 */
function insert2MySQLDB($msg)
{
    $db = Database::getConnection();
    $detailParams = [
        'Article_URL' => $msg['url'],
        'Article_URL_MD5_ID' => md5($msg['url']),
        'Article_Title' => $msg['msg_title'],
        'Article_Abstract' => $msg['msg_abstract'],
        'Record_MD5_ID' => md5($msg['url']),
        'Article_Title_FingerPrint' => md5($msg['msg_title']),
        'Article_Abstract_FingerPrint' => md5($msg['msg_abstract']),
        'Article_PubTime_Str' => $msg['msg_date'],
        'Article_PubTime' => $msg['msg_date'],
        'Article_Search_Keywords' => $msg['keywords'],
        'Website_No' => 'S88888',
        'Media_Type_Code' => 'N',
        'Domain_Code' => 'baidu.com',
        'Is_With_Content' => 1,
        'Article_Source' => '百度',
    ];

    $contentParams = [
        'Article_Record_MD5_ID' => md5($msg['url']),
        'Is_Extract_After_Detail' => '0',
        'Article_Title' => $msg['msg_title'],
        'Article_Content' => $msg['msg_text'],
        'Article_Content_FingerPrint' => md5($msg['msg_text']),
        'Is_Html_Content' => '0',
        'Article_Abstract' => $msg['msg_abstract'],
        'Article_Source' => '百度',
    ];

    // 用事物，如果失败了就回滚
    $db->beginTransaction();

    // Article Detail
    $detailQuery = $db->createQueryBuilder()
        ->insert('article_detail');
    foreach ($detailParams as $dColumn => $value) {
        $detailQuery->setValue($dColumn, ':' . $dColumn);
    }
    $detailQuery->setParameters($detailParams);

    // Article Content
    $contentQuery = $db->createQueryBuilder()
        ->insert('article_content');
    foreach ($contentParams as $cColumn => $value) {
        $contentQuery->setValue($cColumn, ':' . $cColumn);
    }
    $contentQuery->setParameters($contentParams);

    // 执行
    try {
        $detailQuery->execute();
        $contentQuery->execute();
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        return $e->getMessage();
    }

    return true;
    // 删除的时候要记得删除 record_md5_id_unique
}


/**
 * 插入到数据库
 *
 * @param array $msg 一条报文消息
 * @return bool|string 插入成功返回 TRUE 失败返回失败原因
 */
function insert2SQLiteDB($msg)
{
    echo sprintf('[%s]  Insert message company: %s, keywords: %s' . PHP_EOL, date('Y-m-d H:i:s'), $msg['company_name'], $msg['keywords']);
    $db = Database::getConnection([
        'path' => '/home/hfx/workspace/php/php-playground/data/data.sqlite',
        'driver' => 'pdo_sqlite',
    ]);

    $query = $db->createQueryBuilder()
        ->insert('message');
    foreach ($msg as $k => $v) {
        $query->setValue($k, ':' . $k);
    }

    $db->beginTransaction();
    try {
        $query->setParameters($msg)
            ->execute();
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        echo $e->getMessage() . PHP_EOL;
        return $e->getMessage();
    }
    return true;
}


function importFromSQLite()
{
    $db = Database::getConnection([
        'path' => '/home/hfx/workspace/php/php-playground/data/data.sqlite',
        'driver' => 'pdo_sqlite',
    ]);

    $querySQL = <<<EOF
SELECT
  m.*
FROM
  message m
  JOIN (
         SELECT
           count(tm.id) AS unique_article_count,
           company_name
         FROM
           message tm
           JOIN
           (SELECT ID
            FROM
              message
            GROUP BY
              url) t ON t.ID = tm.ID
         GROUP BY
           company_name
         HAVING
           count(tm.id) >= 8
         ORDER BY
           unique_article_count DESC
       ) t ON t.company_name = m.company_name
GROUP BY url
EOF;

    $data = $db->query($querySQL)
        ->fetchAll();
    $arr = [];
    // $mysqlDB = Database::getConnection();

    foreach ($data as $datum) {
        /*$md5ID = md5($datum['url']);
        $query = $mysqlDB->createQueryBuilder()
            ->delete('article_content_record_md5_id')
            ->where('Record_MD5_ID = ?')
            ->setParameter(0, $md5ID);
        echo $query->execute() . "\t" . $md5ID . PHP_EOL;*/
        echo insert2MySQLDB($datum) . PHP_EOL;
    }
    // echo count($data) . PHP_EOL;
}


// 1. 执行合成
// combineFiles($filePath, $resultFilename);

// 2. 执行读取、解析
readResultFile($resultFilename);

// insert2SQLiteDB([]);

// importFromSQLite();