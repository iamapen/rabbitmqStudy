<?php
/**
 * 送信者(pulisher / sender)
 */
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');

// 名前付きqueueの指定、なければ作成
$channel = $con->channel();
$channel->queue_declare('hello');

// メッセージを作り、送る
$msg = new AMQPMessage('Hello World!');
$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$con->close();
