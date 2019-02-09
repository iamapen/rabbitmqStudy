<?php
/**
 * syslog のような facility.severity Emitter (pub/sub pattern)
 *
 * Usage:
 *   php emitLogTopic.php "foo.bar" aaa
 *   php emitLogTopic.php "kern.critical" aaa
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$severity = $argv[1] ?? 'info';

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $con->channel();
// topic exchange autoDelete=false
$channel->exchange_declare('topicLogs', 'topic', false, false, false);

$routingKey = $argv[1] ?? 'anonymous.info';

// メッセージを作り、送る
$data = implode(' ', array_slice($argv, 2));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data);
$channel->basic_publish($msg, 'topicLogs', $routingKey);

echo sprintf(
    " [x] Sent %s:%s\n",
    $routingKey,
    $data
);

$channel->close();
$con->close();
