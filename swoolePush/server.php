
<?php
/**
 * @class   WebsocketService   //swoole Websocket长连接类
 * @author: zhangle
 * @date:   2018/6/26
 * @return: object
 */
class WebsocketService {
    public  $server;
    private $userIdList = array();
    public function __construct() {
        $this->init();
    }

    /**
     * @function    init        //swoole Websocket长连接类
     * @author:     zhangle
     * @date:       2018/6/26
     */
    private function init(){
        try {

            //创建内存数据表
            $table          = new swoole_table(1024);
            $table->column('fid',       swoole_table::TYPE_STRING, 128);
            $table->create();

            //链接swoole_websocket_server服务
            $this->server           = new swoole_websocket_server("0.0.0.0", 9501);
            $this->server->table    = $table;

            $this->server->set(array(
                'worker_num'                => 4,
                'max_request'               => 500,
                'max_conn'                  => 500,
                'dispatch_mode'             => 3,
                'debug_mode'                => 1,
                'daemonize'                 => false,
                'heartbeat_idle_time'       => 40,
                'heartbeat_check_interval'  => 20,

            ));

            //捕获客户端请求
            $this->server->on('open', function (swoole_websocket_server $server, $request) {
                echo $this->messageFormat('open', $request->get['userId'].'_'.$request->fd.' total:' .count($this->server->connections)).PHP_EOL;
                //没有设置message, 那么是客户端来源
                if(!isset($request->post['message'])) {
                    if (isset($request->get['userId']) && !empty($request->get['userId']) && $request->get['userId'] > 0) {
                        $this->server->table->set($request->get['userId'], array('fid' => $request->fd));
                    } else {
                        //参数不正确踢出去链接
                        echo $this->messageFormat('close', 'close'.$request->get['userId'].'_'.$request->fd.' total:' .count($this->server->connections)).PHP_EOL;
                        $this->server->close($request->fd);
                    }
                }
            });

            //客户端请求连接成功, 也可以验证心跳
            $this->server->on('message', function (swoole_websocket_server $server, $frame) {
                $server->push($frame->fd, $this->messageFormat('heartbeat', $frame->fd));
                echo 'heartbeat: '.$frame->fd.PHP_EOL;
            });

            //连接关闭
            $this->server->on('close', function ($ser, $fid) {
                foreach ($this->userIdList as $userId => $fd) {
                    if($fid == $fd){
                        echo 'close userId'.$userId.PHP_EOL;
                        //删除用户绑定信息
                        $this->server->table->del($userId);
                    }
                }
                echo $this->messageFormat('close', 'client '.$userId.' closed\n').PHP_EOL;
            });

            //推送消息  可获取外部数据
            $this->server->on('request', function ($request, $response) {
                if(is_null($request->post['message']) || empty($request->post['message'])){
                    return;
                }
                $message    = json_decode(trim($request->post['message'], true));

                // 接收http请求从get获取message参数的值，给用户推送
                // $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
                //循环发出去的信息
//                $message = array(
//                    'userId的值'   => array(
//                        'key'   => $key,
//                        'value' => $value,
//                        'data'  => array(
//                            'title'     => $title,
//                            'content'   => $content,
//                            'imgUrl'    => $imgUrl,
//                        ),
//                    ),
//
//                );
                if(!empty($message)){
                    foreach ($message as $userId => $value) {
                        //TODO 如何判断连接是否为WebSocket客户端 参考地址:https://wiki.swoole.com/wiki/page/490.html
                        //从内存表中取出当前用户的信息
                        $fidInfo    = $this->server->table->get($userId);
                        print_r($fidInfo);
                        //如果信息存在,以及当前链接保持 那么发送数据
                        if(!empty($fidInfo) && is_array($fidInfo)){
                            $fd = $fidInfo['fid'];
                            $value  = $this->messageFormat('message', $value);

                            if($this->server->connection_info($fd)['websocket_status'] == 3){
                                if(1 == $this->server->push($fd, $value)){
                                    echo PHP_EOL.'我要给' .$userId.'_'. $fd . ' push消息【' . $value . '】链接总数:' .count($this->server->connections). PHP_EOL;
                                }
                            }
                        }

                    }
                }

//                foreach ($this->server->connections as $fd) {
//
//                    //TODO 如何判断连接是否为WebSocket客户端 参考地址:https://wiki.swoole.com/wiki/page/490.html
//                    if($this->server->connection_info($fd)['websocket_status'] == 3){
//                        if(1 == $this->server->push($fd, $data)){
//                            echo PHP_EOL.'我要给' . $fd . ' push消息【' . $data . '】链接总数:' .count($this->server->connections). PHP_EOL;
//                        }
//                    }
//
//                }
            });
            $this->server->start();
        }catch (Exception $e){
            echo $this->messageFormat('Exception', $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @function    messageFormat   //返回数据格式化
     * @param       string  $type       类型  heartbeat【握手成功】, close【关闭】, message【推送内容】
     * @param       string  $data       推送内容    default ''
     * @param       int     $code       状态码      default 200
     * @author:     zhangle
     * @date:       2018/6/26
     * @return:     string
     */
    private function messageFormat($type, $data = '', $code = 200){
        $data   = array(
            'type'  => $type,
            'code'  => $code,
            'data'  => $data,
        );

        return json_encode($data);
    }
}

//调起代码
new WebsocketService();
