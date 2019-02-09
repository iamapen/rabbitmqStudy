<?php
/**
 * syslog のような facility.severity Subscriber (pub/sub pattern)
 *
 * Usage:
 *   php receiveLogsTopic.php "#"
 *   php receiveLogsTopic.php "kern.*"
 *   php receiveLogsTopic.php "*.critical"
 *   php receiveLogsTopic.php "kern.*" "*.critical"
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// binding key が必須
$bindingKeys = array_slice($argv, 1);
if (empty($bindingKeys)) {
    file_put_contents('php://stderr', "Usage: $argv[0] [binding_key]\n");
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
// topic exchange autoDelete=false
$channel->exchange_declare('topicLogs', 'topic', false, false, false);

// 排他なキュー作成
list($queueName) = $channel->queue_declare('', false, false, true, false);
// binding
foreach ($bindingKeys as $bindingKey) {
    $channel->queue_bind($queueName, 'topicLogs', $bindingKey);
}

// 受信待ち受け
echo " [*] Waiting for messages. To exit press CTRL+C\n";
// noAck=true
$channel->basic_consume($queueName, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$con->close();
