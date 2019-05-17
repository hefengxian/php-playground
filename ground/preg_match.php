<?php
/*
* Copyright © 2018-present, Knowlesys, Ltd.
* All rights reserved.
* 
* preg_match.php
* 
* Author: HFX 2018-11-13 12:41
*/

$string = '[{"PERSON_REPORTED_IP_ID":1,"DATA_FILE_ID":758,"IP":"78.95.161.13","IP_LOCAL_TIME":"2017\/03\/12","RESPONSE_TEXT":"\n966504523161","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:02","STATUS":3276,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":2,"DATA_FILE_ID":758,"IP":"95.184.102.145","IP_LOCAL_TIME":"2017\/03\/15","RESPONSE_TEXT":"966568228222\n8:51:05\n","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:02","STATUS":3278,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":3,"DATA_FILE_ID":758,"IP":"93.168.79.69","IP_LOCAL_TIME":"2017\/03\/14","RESPONSE_TEXT":"MSISDN:  966547123123\nName:  TAYYAB HASSAN TAYYAB\nID No.:  2123456789\n","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:03","STATUS":3280,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":4,"DATA_FILE_ID":758,"IP":"95.184.7.157","IP_LOCAL_TIME":"2017\/03\/14","RESPONSE_TEXT":"MSISDN:  966547123123\nName:  TAYYAB HASSAN waw\nID No.:  2123456789\n","ASSIGN_TIME":null,"AIR_ID":"2019\/03\/26","STATUS":"13:18:05","NOTES":"3290"},{"PERSON_REPORTED_IP_ID":5,"DATA_FILE_ID":758,"IP":"95.184.84.85","IP_LOCAL_TIME":"2017\/03\/13","RESPONSE_TEXT":"ACCESS_TYPE: FTTH \nCITY: HUFUF \nMACID: #ac:61:75:92:d0:50 \nACCOUNT_NAME: Mr. teto teso \nNATIONALITY: India \nMOBILE_NUMBER: #0555429333 \nEMAIL_ADDRESS: aimti333@yahoo.com \nID_NUMBER: #24084133 \nID_TYPE: iqama ","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:05","STATUS":3291,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":6,"DATA_FILE_ID":758,"IP":"93.168.135.158","IP_LOCAL_TIME":"2017\/03\/13","RESPONSE_TEXT":"114860851","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:08","STATUS":3305,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":7,"DATA_FILE_ID":758,"IP":"95.184.1.104","IP_LOCAL_TIME":"2017\/03\/13","RESPONSE_TEXT":"966555096569\n\n0:44:33 ","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:08","STATUS":3306,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":8,"DATA_FILE_ID":758,"IP":"95.184.49.39","IP_LOCAL_TIME":"2017\/03\/12","RESPONSE_TEXT":"966540363888","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:08","STATUS":3307,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":9,"DATA_FILE_ID":758,"IP":"151.254.0.222","IP_LOCAL_TIME":"2017\/03\/12","RESPONSE_TEXT":"ACCESS_TYPE:  LTE\nCITY:  ABHA\nMACID:  #420016500525157\nACCOUNT_NAME:  Mr. Saad Elsaleh\nNATIONALITY:  Saudi Arabia\nMOBILE_NUMBER:  #0505475695\nEMAIL_ADDRESS:  anr426@hotmail.com\nID_NUMBER:  #1031782772\nID_TYPE:  saudiId\n\nACCESS_TYPE:  LTE\nCITY:  RIYADH\nMACID:  #420016500588650\nACCOUNT_NAME:  Mr. Mohamed Rdoy\nNATIONALITY:  Bangladesh\nMOBILE_NUMBER:  #0560753196\nEMAIL_ADDRESS:  Nvvnjhfd@hotmail.com\nID_NUMBER:  #2399334636\nID_TYPE:  iqama\n\nACCESS_TYPE:  LTE\nCITY:  AL-KHARJ\nMACID:  #420016500641073\nACCOUNT_NAME:  Dr. Mohammad Shahadat\nNATIONALITY:  Bangladesh\nMOBILE_NUMBER:  #0556197155\nEMAIL_ADDRESS:  babu4@gmail.com\nID_NUMBER:  #2427406349\nID_TYPE:  iqama\n\nACCESS_TYPE:  LTE\nCITY:  RIYADH\nMACID:  #420016500640942\nACCOUNT_NAME:  Mr. Mostafamiah Surajmiah\nNATIONALITY:  Bangladesh\nMOBILE_NUMBER:  #0577142382\nEMAIL_ADDRESS:  mostafa009@gmail.com\nID_NUMBER:  #2093588560\nID_TYPE:  iqama\n\nACCESS_TYPE:  LTE\nCITY:  HAFER-AL-BATEN\nMACID:  #420016500640056\nACCOUNT_NAME:  Mr. Ahmed Elsaed\nNATIONALITY:  Saudi Arabia\nMOBILE_NUMBER:  #0548048087\nEMAIL_ADDRESS:  grfghgth@gmail.com\nID_NUMBER:  #1009186147\nID_TYPE:  saudiId\n\nACCESS_TYPE:  LTE\nCITY:  RIYADH\nMACID:  #420016500265322\nACCOUNT_NAME:  Mr. Yosef El harby\nNATIONALITY:  Saudi Arabia\nMOBILE_NUMBER:  #0506391827\nEMAIL_ADDRESS:  Ytr.po@hotmail.com\nID_NUMBER:  #1064353855\nID_TYPE:  saudiId\n\nACCESS_TYPE:  LTE\nCITY:  RIYADH\nMACID:  #420016500224998\nACCOUNT_NAME:  Mr. Blal Radman\nNATIONALITY:  Yemen\nMOBILE_NUMBER:  #0509351713\nEMAIL_ADDRESS:  Tyasdfg@Hotmail.com\nID_NUMBER:  #2169509565\nID_TYPE:  iqama\n\nACCESS_TYPE:  LTE\nCITY:  RIYADH\nMACID:  #420016500571868\nACCOUNT_NAME:  Mr. Gardeon Tayone\nNATIONALITY:  Philippines\nMOBILE_NUMBER:  #0594292515\nEMAIL_ADDRESS:  zahidhossain1926@gmail.com\nID_NUMBER:  #2410669689\nID_TYPE:  iqama\n\nACCESS_TYPE:  LTE\nCITY:  RIYADH\nMACID:  #420016500643589\nACCOUNT_NAME:  Mr. Joey Vanzuela\nNATIONALITY:  Philippines\nMOBILE_NUMBER:  #0502716774\nEMAIL_ADDRESS:  saeedsaddam364@yahoo.com\nID_NUMBER:  #2432212013\nID_T","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:09","STATUS":3315,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":10,"DATA_FILE_ID":758,"IP":"151.254.0.229","IP_LOCAL_TIME":"2017\/03\/12","RESPONSE_TEXT":"14492061","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:10","STATUS":3316,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":11,"DATA_FILE_ID":758,"IP":"78.95.161.13","IP_LOCAL_TIME":"2017\/03\/12","RESPONSE_TEXT":"\u0644\u0627 \u064a\u0648\u062c\u062f \u0628\u064a\u0627\u0646\u0627\u062a \u0645\u0639 \u0627\u0644\u0628\u0648\u0631\u062a\n\n\u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a \u0623\u062f\u0646\u0627\u0647 \u062f\u0648\u0646 \u0627\u0644\u0628\u0648\u0631\u062a \n966831042028333 \n966831044631333\n18:14:46\n","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:10","STATUS":3318,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":12,"DATA_FILE_ID":758,"IP":"78.95.161.13","IP_LOCAL_TIME":"2017\/03\/12","RESPONSE_TEXT":"63420164@stc.net.sa\n607-00_MDNBQI00OL0 pon 0\/01\/6\/078:0010#16-342-0164 ","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:10","STATUS":3319,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":13,"DATA_FILE_ID":758,"IP":"78.95.161.13","IP_LOCAL_TIME":"2017\/03\/12","RESPONSE_TEXT":"MSISDN:  966547124444\nName:  TAYYAB HASSAN TAYYAB\nID No.:  0003456789\n","ASSIGN_TIME":"2019\/03\/26","AIR_ID":"13:18:11","STATUS":3324,"NOTES":"Checked"},{"PERSON_REPORTED_IP_ID":14,"DATA_FILE_ID":758,"IP":"95.187.192.93","IP_LOCAL_TIME":"2018\/07\/22","RESPONSE_TEXT":"Reply","ASSIGN_TIME":null,"AIR_ID":"2019\/03\/26","STATUS":"13:15:38","NOTES":"2555"},{"PERSON_REPORTED_IP_ID":15,"DATA_FILE_ID":758,"IP":"95.187.224.224","IP_LOCAL_TIME":"2018\/07\/22","RESPONSE_TEXT":"IP","ASSIGN_TIME":"or","AIR_ID":"private","STATUS":"IP..","NOTES":""},{"PERSON_REPORTED_IP_ID":16,"DATA_FILE_ID":758,"IP":"2001:16a2:404:bd56:488c:7ec2:8ea4:f988","IP_LOCAL_TIME":"2018\/07\/22","RESPONSE_TEXT":"Reply","ASSIGN_TIME":null,"AIR_ID":"2019\/03\/26","STATUS":"13:16:08","NOTES":"2706"},{"PERSON_REPORTED_IP_ID":17,"DATA_FILE_ID":758,"IP":"2.90.192.53","IP_LOCAL_TIME":"2018\/07\/21","RESPONSE_TEXT":"Reply","ASSIGN_TIME":"CITC","AIR_ID":null,"STATUS":"2019\/03\/26","NOTES":"13:16:14"}]';

/*$tmpData = [];

$lines = explode(PHP_EOL, $string);

foreach ($lines as $line) {
    $line = trim($line);
    $regExp = '/IP Address(.*)(\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}) UTC/i';
    if (preg_match($regExp, $line, $matches)) {
        if (count($matches) == 3) {
            $ipAndEvent = $matches[1];
            $time = $matches[2];
            $ipAndEvent = preg_split('/\s+/', trim($ipAndEvent));
            if (count($ipAndEvent) == 2) {
                $ip = $ipAndEvent[0];
                $tmp = [
                    'IP' => $ip,
                    'DateTime' => $time,
                ];
                $tmpData['UploadFileInfo'][] = $tmp;
            }
        }
    }
}

if ($tmpData['UploadFileInfo']) {
    echo json_encode($tmpData['UploadFileInfo']);
}*/

$data = json_decode($string, true);

foreach ($data as $datum) {
    $response = $datum['RESPONSE_TEXT'];
    $dataWeWant = '';
    echo $response . PHP_EOL;
    if (preg_match('/([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/i', $response, $matches)) {
        print_r($matches);
    }


    if (!empty($dataWeWant))
    echo '===+++ Matched: ', $dataWeWant, ' ++++++++++++++', PHP_EOL, PHP_EOL, PHP_EOL, PHP_EOL ;
}
