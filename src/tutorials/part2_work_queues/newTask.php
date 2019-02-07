<?php
/**
 * 送信者(pulisher / sender)
 *
 * Usage:
 *   php newTask.php ..(2秒かかる処理)
 *   php newTask.php .....(5秒かかる処理)
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

// 名前付きqueueの指定、なければ作成
$channel = $con->channel();
$channel->queue_declare('hello');

// メッセージを作り、送る
$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data);
$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent ${data}\n";

$channel->close();
$con->close();
