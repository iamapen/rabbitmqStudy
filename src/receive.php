<?php
/**
 * 受信者(consumer / receiver)
 */
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// メッセージ受信時のcallback
$callback = function (AMQPMessage $msg) {
    echo ' [x] Recieved ', $msg->body, "\n";
};

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $con->channel();
// 名前付きqueueの指定、なければ作成
$channel->queue_declare('hello');

$channel->basic_consume('hello', '', false, false, false, false, $callback);

// 受信待ち受け
echo " [*] Waiting for messages. To exit press CTRL+C\n";
while (count($channel->callbacks)) {
    $channel->wait();
}
