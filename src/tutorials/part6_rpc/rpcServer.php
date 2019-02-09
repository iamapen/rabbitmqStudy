<?php
/**
 * フィボナッチ数を返すRPC-Serever
 *
 * 時間のかかる処理をフィボナッチ数算出でシミュレーション
 */
require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * フィボナッチ数を返す(最も遅い再帰的な実装)
 * @param int $n
 * @return int
 */
function fib(int $n)
{
    if ($n === 0) {
        return 0;
    }
    if ($n === 1) {
        return 1;
    }
    return fib($n - 1) + fib($n - 2);
}

// メッセージ受信時のcallback
$callback = function (AMQPMessage $req) {
    $n = intval($req->body);
    echo ' [.] fib(', $n, ")\n";

    // フィボナッチ数を含むメッセージ
    $msg = new AMQPMessage(
        (string)fib($n),
        // 相関IDを付ける
        ['correlation_id' => $req->get('correlation_id')]
    );
    // reply送信
    $req->delivery_info['channel']->basic_publish(
        $msg,
        '',
        $req->get('reply_to')
    );
    // ACK
    $req->delivery_info['channel']->basic_ack(
        $req->delivery_info['delivery_tag']
    );
};

// 接続
$con = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $con->channel();
// autoDelete=false
$channel->queue_declare('rpcQueue', '', false, false, false, false);
// busyなworkerには送信しないように
$channel->basic_qos(null, 1, null);

$channel->basic_consume('rpcQueue', '', false, false, false, false, $callback);

// 受信待ち受け
echo " [x] Awaiting RPC requests\n";
while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$con->close();
