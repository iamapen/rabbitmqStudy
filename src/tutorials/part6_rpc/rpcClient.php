<?php
/**
 * フィボナッチ数を返すRPCのクライアント
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class FibonacciRpcClient
{
    /** @var AMQPStreamConnection */
    private $con;
    /** @var \PhpAmqpLib\Channel\AMQPChannel */
    private $channel;
    /** @var string callback queue の名前 */
    private $cbQueueName;
    /** @var int RPCから返されたフィボナッチ数 */
    private $response;
    /** @var string 相関ID */
    private $corrId;

    public function __construct()
    {
        $this->con = new AMQPStreamConnection(
            'localhost',
            5672,
            'guest',
            'guest'
        );
        $this->channel = $this->con->channel();
        // クライアント単位に排他なqueue
        list($this->cbQueueName) = $this->channel->queue_declare(
            '',
            false,
            false,
            true,
            false
        );
        // noAck=true, callback
        $this->channel->basic_consume(
            $this->cbQueueName,
            '',
            false,
            true,
            false,
            false,
            [$this, 'onResponse']
        );
    }

    /**
     * レスポンスがあった場合のcallback
     * @param AMQPMessage $rep
     */
    public function onResponse(AMQPMessage $rep)
    {
        // 相関IDが同じ場合だけを有効とみなす
        if ($rep->get('correlation_id') === $this->corrId) {
            $this->response = $rep->body;
        }
    }

    /**
     * RPC実行
     * @param int $n
     * @return int フィボナッチ数
     * @throws ErrorException
     */
    public function call(int $n)
    {
        $this->response = null;
        $this->corrId = uniqid();

        // 相関IDとcallback queueを指定したメッセージ
        $msg = new AMQPMessage(
            (string)$n,
            [
                'correlation_id' => $this->corrId,
                'reply_to' => $this->cbQueueName,
            ]
        );
        // 送信
        $this->channel->basic_publish($msg, '', 'rpcQueue');
        // レスポンスを待つ
        while (!$this->response) {
            $this->channel->wait();
        }

        return intval($this->response);
    }
}

// 実行
$client = new FibonacciRpcClient();
$res = $client->call(30);
echo ' [.] Got ', $res, "\n";
