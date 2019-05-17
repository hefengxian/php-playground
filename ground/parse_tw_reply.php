<?php
/*
* Copyright © 2019-present, Knowlesys, Ltd.
* All rights reserved.
* 
* parse_tw_reply.php
* 
* Author: HFX 2019-01-02 18:02
*/

$tlJson = file_get_contents(__DIR__ . '/../data/chinaorgcn1031738834508111873.json');

$tlData = json_decode($tlJson, true);

$tlHTML = $tlData['items_html'];
// echo $tlHTML;

// file_put_contents(__DIR__ . '/../data/tw_chinaorgcn1031738834508111873_reply.html', $tlHTML);

function parseMsgReply($rawData, $msgID = '1031738834508111873') {
    if (preg_match_all('/<li class="js-stream-item.*?>(.*?<ul>.*?<\/ul>.*?){1}<\/li>/is', $rawData, $matches) !== false) {
        foreach ($matches[1] as $tweet) {

            // 获取默认消息
            // $msg = Config::message();
            $msg = [];
            $msg['gt'] = time();

            // 类型 1:新发布微博,2:评论微博,3:转发微博 -1
            $msg['wb_msg_type'] = 2;


            // 获取一段有用的 JSON 数据，可以得到原作者已经转发作者的信息
            $replyJsonData = [];
            if (preg_match_all('/data-reply-to-users-json="(\[.*?\])"/is', $tweet, $mch)) {
                $jsonStr = html_entity_decode($mch[1][0]);
                $replyJsonData = json_decode($jsonStr, true);
            }

            // 回复、转发、点赞
            $replyCount = -1;
            $forwardCount = -1;
            $likeCount = -1;
            if (preg_match_all('/data-tweet-stat-count="(\d+)".*?data-tweet-stat-count="(\d+)".*?data-tweet-stat-count="(\d+)"/is', $tweet, $mch) !== false) {
                $replyCount = $mch[1][0];
                $forwardCount = $mch[2][0];
                $likeCount = $mch[3][0];

                $msg['nrply'] = intval($replyCount);
                $msg['nfwd'] = intval($forwardCount);
                $msg['nlike'] = intval($likeCount);
            }

            // Twitter ID
            $mid = '';
            if (preg_match_all('/data-tweet-id="(\d+)"/is', $tweet, $mch) !== false) {
                $mid = $mch[1][0];
                $msg['wb_mid'] = $mid;
            }

            $msg['uid'] = $replyJsonData[0]['id_str'];

            // Screen Name
            $screenName = '';
            if (preg_match_all('/data-screen-name="(\w+)"/is', $tweet, $mch) !== false) {
                $screenName = $mch[1][0];
                $msg['sname'] = $screenName;
            }


            // Publish Time
            if (preg_match_all('/data-time="(\d+)"/is', $tweet, $mch) !== false) {
                $publishTime = $mch[1][0];
                $msg['pt'] = intval($publishTime);
            }

            // 图片
            if (preg_match_all('/data-image-url="(.*?)"/is', $tweet, $mch) !== false) {
                $images = $mch[1];
                foreach ($images as $image) {
                    $msg['lpic'][] = $image;
                }
            }

            // 正文
            $content = '';
            if (preg_match_all('/<p class=".*?js-tweet-text tweet-text".*?>(.*?)<\/p>/is', $tweet, $mch) !== false) {
                $content = $mch[1][0];

                // 提取 @ mentioned
                if (preg_match_all('/<s>@<\/s>.*?<b>(.*?)<\/b>/is', $content, $mch) !== false) {
                    $mentions = $mch[1];
                    foreach ($mentions as $mention) {
                        $msg['wb_lat_sname'][] = $mention;
                    }
                }
                // 提取 # hash tag
                if (preg_match_all('/<s>#<\/s>.*?<b>(.*?)<\/b>/is', $content, $mch) !== false) {
                    $hashTags = $mch[1];
                    foreach ($hashTags as $hashTag) {
                        $msg['wb_lhashtag'][] = $hashTag;
                    }
                }

                // 处理链接没有和正文间隔一个空格问题
                $content = preg_replace('/<a.*?>(.*?)<\/a>/is', ' ${1}', $content);
                // 处理 HTML 实体
                $content = html_entity_decode($content);
                // 去掉标签
                $content = strip_tags($content);
                // 去掉多余空格(HTML 实体里的空格去不掉)
                $content = preg_replace('/\s{2,}/is', ' ', $content);

                $msg['cont'] = $content;
            }

            // 语种
            if (preg_match_all('/lang="(\w+)"/is', $tweet, $mch) !== false) {
                $lang = $mch[1][0];
                $msg['lang'] = $lang;
            }

            // URL
            $msgURL = sprintf('https://twitter.com/%s/status/%s', $screenName, $mid);
            $msg['url'] = $msgURL;

            print_r($msg);
        }
    }
}

parseMsgReply($tlHTML);