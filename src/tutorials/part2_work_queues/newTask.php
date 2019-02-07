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
$channel = $con->channel();
// durable=true (MQサーバが停止してもメッセージが失われないqueue)
$channel->queue_declare('task01', false, true);

// メッセージを作り、送る
$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
$channel->basic_publish($msg, '', 'task01');

echo " [x] Sent ${data}\n";

$channel->close();
$con->close();
