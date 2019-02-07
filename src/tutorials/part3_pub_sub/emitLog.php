<?php
/**
 * Emitter (pub/sub pattern)
 *
 * ログを出力する
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $con->channel();
// broadcast exchange
$channel->exchange_declare('logs', 'fanout', false, false, false);

// メッセージを作り、送る
$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data);
$channel->basic_publish($msg, 'logs');

echo " [x] Sent ${data}\n";

$channel->close();
$con->close();
