<?php
/**
 * severity付きEmitter (pub/sub pattern)
 *
 * Usage:
 *   php emitLog.php warn aaa
 *   php emitLog.php error bbb
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$severity = $argv[1] ?? 'info';

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $con->channel();
// direct exchange autoDelete=false
$channel->exchange_declare('directLogs', 'direct', false, false, false);

// メッセージを作り、送る
$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data);
$channel->basic_publish($msg, 'directLogs', $severity);

echo sprintf(
    " [x] Sent %s:%s\n",
    $severity,
    $data
);

$channel->close();
$con->close();
