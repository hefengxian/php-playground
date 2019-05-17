<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* solarium_test.php
* 
* Author: HFX 2018-06-21 17:21
*/

require_once('../vendor/autoload.php');

echo date('Y-m-d\\TH:i:s\\Z', strtotime('2018-06-21 00:00:00') - date('Z')) . PHP_EOL;
echo date('Z') . PHP_EOL;
echo date_default_timezone_get() . PHP_EOL;

exit;

$client = new \Solarium\Client([
    'endpoint' => [
        'article_search' => [
            'host' => '192.168.1.114',
            'port' => 8081,
            'path' => '/solr/db_article/',
            'timeout' => 30,
        ],
    ]
]);

$query = $client->createSelect();

var_dump($query);

exit;

$query = $client->createAnalysisField([
    'fieldname' => 'article_content',
    'fieldvalue' => 'article_content',
    'showmatch' => true,
]);

$results = $client->analyze($query);

foreach ($results as $result) {
    echo $result->getName() . PHP_EOL;
    foreach ($result as $item) {

        echo '<h3>Item: ' . $item->getName() . '</h3>';

        $indexAnalysis = $item->getIndexAnalysis();
        if (!empty($indexAnalysis)) {
            foreach ($indexAnalysis as $classes) {
                foreach ($classes as $result) {
                    echo 'Text: ' . $result->getText() . '<br/>';
                    echo 'Raw text: ' . $result->getRawText() . '<br/>';
                    echo 'Start: ' . $result->getStart() . '<br/>';
                    echo 'End: ' . $result->getEnd() . '<br/>';
                    echo 'Position: ' . $result->getPosition() . '<br/>';
                    echo 'Position history: ' . implode(', ', $result->getPositionHistory()) . '<br/>';
                    echo 'Type: ' . htmlspecialchars($result->getType()) . '<br/>';
                    echo 'Match: ' . var_export($result->getMatch(), true) . '<br/>';
                    echo '-----------<br/>';
                }
            }
        }


    }
}