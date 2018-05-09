<?php
/*
* Copyright © 2018-present, Knowlesys, Inc.
* All rights reserved.
* 
* phpexcel.php
* 
* Author: HFX 2018-03-19 11:47
*/

require(__DIR__ . '/../vendor/autoload.php');

$excelVersion = 'Excel2007';
$path = '/home/hfx/Desktop/output_excel/';
$template = $path . 'user_workload_rank_template.xlsx';
$output = $path . 'output.xlsx';

$reader = PHPExcel_IOFactory::createReader($excelVersion);
$excel = $reader->load($template);
$sheet = $excel->getSheetByName('All User');
// echo $sheet->getCell('E4')->getValue();
// 第一步插入行！
$sheet->insertNewRowBefore(5, 60);


$write = PHPExcel_IOFactory::createWriter($excel, $excelVersion);
$write->save($output);

