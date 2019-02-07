<?php
/**
 * severity Subscriber (pub/sub pattern)
 *
 * Usage:
 *   php receiveLogs.php info
 *   php receiveLogs.php warn
 *   php receiveLogs.php error
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 先頭の引数をseverityとする
$severities = array_slice($argv, 1);
if (empty($severities)) {
    file_put_contents('php://stderr', "Usage: $argv[0] [info] [warning] [error]\n");
    exit(1);
}

// メッセージ受信時のcallback
$callback = function (AMQPMessage $msg) {
    echo sprintf(
        " [x]%s:%s\n",
        $msg->delivery_info['routing_key'],
        $msg->body
    );
};

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $con->channel();
// broadcast exchange autoDelete=false
$channel->exchange_declare('logs', 'fanout', false, false, false);

// 排他なキュー作成、使用
list($queueName) = $channel->queue_declare('', false, false, true, false);

// severity をキーにbind
foreach ($severities as $severity) {
    $channel->queue_bind($queueName, 'directLogs', $severity);
}
// noAck=true
$channel->basic_consume($queueName, '', false, true, false, false, $callback);

// 受信待ち受け
echo " [*] Waiting for messages. To exit press CTRL+C\n";
while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$con->close();
