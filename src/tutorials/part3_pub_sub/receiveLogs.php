<?php
/**
 * Subscriber (pub/sub pattern)
 *
 * 複数起動させておくとブロードキャストを受けるのを確認できる
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// メッセージ受信時のcallback
$callback = function (AMQPMessage $msg) {
    echo ' [x] Recieved ', $msg->body, "\n";
};

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $con->channel();
// broadcast exchange
$channel->exchange_declare('logs', 'fanout', false, false, false);

// 排他なキュー作成、使用
list($queueName) = $channel->queue_declare('', false, false, true, false);
// exchangeをbind
$channel->queue_bind($queueName, 'logs');

$channel->basic_consume($queueName, '', false, true, false, false, $callback);

// 受信待ち受け
echo " [*] Waiting for messages. To exit press CTRL+C\n";
while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$con->close();
