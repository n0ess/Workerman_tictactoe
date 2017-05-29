<?php

use Workerman\Lib\Timer;
use Workerman\Worker;
require_once __DIR__ . '/workerman/Autoloader.php';
define('HEARTBEAT_TIME', 20);
//创建端口
$ws = new Worker("websocket://127.0.0.1:7272");
session_start();

//启动进程数
$ws->count = 1;

//设置uid
$uid = 0;

// 当客户端连上来时分配uid，并保存连接，并通知所有客户端
$ws->onConnect = function ($connection) {
    global $ws, $uid;
    // 为这个链接分配一个uid
    $connection->uid = ++$uid;
};

//接受数据并返回
$ws->onMessage = function ($connection, $json) {
    global $ws;
    $data = json_decode($json, true);
    if (!$data) {return;}
    $connection->lastMessageTime = time();
    $re = array();
    if ($data['type'] == 'post') {
        $_SESSION['data'][$data['key']][] = $data['num'];
        $rs = runon($_SESSION['data'][$data['key']]);
        if ($rs !== false) {
            $data['wdata'] = $rs;
            $data['win'] = $data['key'];
        }
    }

    if ($data['type'] == 'login') {
        $num = 0;
        foreach ($ws->connections as $conn) {
            $num++;
            if($num > 2){
                $conn->send(json_encode(array('type'=>"goout")));
                $conn->close();
            }
        }

        if($num >= 2){
            $data['num'] = 2;
            $data['can'] = 1;
        }
    }
    if ($data['type'] == 'pong') {
        return;
    }
    $re = json_encode($data);
    foreach ($ws->connections as $conn) {
        $conn->send($re);
    }
};

//客户端连接断开，广播给所有客户端
$ws->onClose = function ($connection) {
    global $ws;
    unset($_SESSION);
    $re['type'] = "logout";
    foreach ($ws->connections as $conn) {
        $conn->send(json_encode($re));
    }
};

//心跳包
$ws->onWorkerStart = function ($ws) {
    Timer::add(19, function () use ($ws) {
        $time_now = time();
        foreach ($ws->connections as $conn) {
            $conn->send(json_encode(array('type' => 'ping')));
            // 有可能该conn还没收到过消息，则lastMessageTime设置为当前时间
            if (empty($conn->lastMessageTime)) {
                $conn->lastMessageTime = $time_now;
                continue;
            }
            // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
            if ($time_now - $conn->lastMessageTime > HEARTBEAT_TIME) {
                $conn->close();
            }
        }
    });
};
//报错输出
$ws->onError = function($connection, $code, $msg)
{
    echo "error $code $msg\n";
};
//处理
function runon($on)
{   
    $rs = false;
    $win[] = array(0,1,2);
    $win[] = array(3,4,5);
    $win[] = array(6,7,8);
    $win[] = array(0,3,6);
    $win[] = array(1,4,7);
    $win[] = array(2,5,8);
    $win[] = array(0,4,8);
    $win[] = array(2,4,6);
    foreach ($win as $k => $v) {
        if(in_array($v[0], $on) && in_array($v[1], $on) && in_array($v[2], $on)){
            $rs = $v;
            break;
        }
    }
    return $rs;

}


Worker::runAll();
