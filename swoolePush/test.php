<?php
require_once 'curl.php';
$message = array(
    '4103478'   => array(
        'key'   => 'goodDetail',
        'value' => '423933',
        'data'  => array(
            'title'     => '恭喜入账100元!',
            'content'   => '恭喜入账100元,这里是内容',
            'imgUrl'    => 'http://videocdn.tlgn365.com/thumb/2018-07-07/15309289201481.jpg'
        ),
    ),
//    '11983'     => array(
//        'key'   => 'getFans',
//        'value' => '',
//        'data'  => array(
//            'title'     => '我在测试Android消息推送!',
//            'content'   => '我在测试Android消息推送,这里是内容',
//            'imgUrl'    => 'http://videocdn.tlgn365.com/thumb/2018-07-07/15309289201481.jpg'
//        ),
//    ),
);
//file_get_contents('http://59.110.104.78:9501/?message='.json_encode($message));
$url    = 'http://59.110.104.78:9501';
curlPost($url, array('message' => json_encode($message)));
echo '推送消息成功!';