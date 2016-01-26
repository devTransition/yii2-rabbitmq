<?php

namespace devtransition\rabbitmq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\Json;


class Connection extends Component
{
    const TYPE_TOPIC = 'topic';
    const TYPE_DIRECT = 'direct';
    const TYPE_HEADERS = 'headers';
    const TYPE_FANOUT = 'fanout';

    /**
     * @var AMQPStreamConnection
     */
    protected static $connection;
    /**
     * @var string
     */
    public $host = '127.0.0.1';
    /**
     * @var integer
     */
    public $port = 5672;
    /**
     * @var string
     */
    public $user;
    /**
     * @var string
     */
    public $password;
    /**
     * @var string
     */
    public $vhost = '/';
    /**
     * @var boolean
     */
    public $consumeNoAck = true;
    /**
     * @var AMQPChannel[]
     */
    protected $channels = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (empty($this->user)) {
            throw new Exception("Parameter 'user' was not set for AMQP connection.");
        }

        if (empty(self::$connection)) {
            self::$connection = new AMQPStreamConnection(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vhost
            );
        }
    }

    /**
     * Returns AMQP connection.
     *
     * @return AMQPStreamConnection
     */
    public function getConnection()
    {
        return self::$connection;
    }

    /**
     * Returns AMQP connection.
     *
     * @param string $channel_id
     * @return AMQPChannel
     */
    public function getChannel($channel_id = null)
    {
        $index = $channel_id ?: 'default';
        if (!array_key_exists($index, $this->channels)) {
            $this->channels[$index] = $this->connection->channel($channel_id);
        }
        return $this->channels[$index];
    }

    /**
     * Sends message to the exchange.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param string|array $message
     * @param string $type Use self::TYPE_DIRECT if it is an answer
     * @return void
     */
    public function send($exchange, $routing_key, $message, $headers = null, $type = self::TYPE_TOPIC)
    {
        $properties = [];
        if ($headers !== null) {
            $properties['application_headers'] = $headers;
        }
        $message = $this->prepareMessage($message, $properties);
        if ($type == self::TYPE_TOPIC) {
            $this->channel->exchange_declare($exchange, $type, false, true, false);
        }
        $this->channel->basic_publish($message, $exchange, $routing_key);
    }

    /**
     * Returns prepaired AMQP message.
     *
     * @param string|array|object $message
     * @param array $properties
     * @return AMQPMessage
     * @throws Exception If message is empty.
     */
    public function prepareMessage($message, $properties = null)
    {
        if (empty($message)) {
            throw new Exception('AMQP message can not be empty');
        }
        if (is_array($message) || is_object($message)) {
            $message = Json::encode($message);
        }
        return new AMQPMessage($message, $properties);
    }

    /**
     * Sends message to the exchange and waits for answer.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param string|array $message
     * @param integer $timeout Timeout in seconds.
     * @return string
     */
    public function ask($exchange, $routing_key, $message, $timeout)
    {
        list ($queueName) = $this->channel->queue_declare('', false, false, true, false);
        $message = $this->prepareMessage($message, [
            'reply_to' => $queueName,
        ]);
        // queue name must be used for answer's routing key
        $this->channel->queue_bind($queueName, $exchange, $queueName);

        $response = null;
        $callback = function (AMQPMessage $answer) use ($message, &$response) {
            $response = $answer->body;
        };

        $this->channel->basic_consume($queueName, '', false, false, false, false, $callback);
        $this->channel->basic_publish($message, $exchange, $routing_key);
        while (!$response) {
            // exception will be thrown on timeout
            $this->channel->wait(null, false, $timeout);
        }
        return $response;
    }

    /**
     * Listens the exchange for messages.
     *
     * @param string $exchange
     * @param string $routing_key
     * @param callable $callback
     * @param string $type
     */
    public function listen($exchange, $routing_key, $callback, $type = self::TYPE_TOPIC, $queue = '')
    {
        $queueName = $queue;
        if (!$queueName) {
            list ($queueName) = $this->channel->queue_declare();
            if ($type == self::TYPE_DIRECT) {
                $this->channel->exchange_declare($exchange, $type, false, true, false);
            }
        }
        $this->channel->queue_bind($queueName, $exchange, $routing_key);
        $this->channel->basic_consume($queueName, '', false, $this->consumeNoAck, false, false, $callback);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }

        $this->channel->close();
        $this->connection->close();
    }

    /**
     * Listens the queue for messages.
     *
     * @param string $queueName
     * @param callable $callback
     */
    public function listenQueue($queueName, $callback, $break = false)
    {
        while (true) {
            if (($message = $this->channel->basic_get($queueName, $this->consumeNoAck)) instanceof AMQPMessage) {
                call_user_func($callback, $message);
            } else {
                if ($break !== false) {
                    break;
                }
            }
        }

        $this->channel->close();
        $this->connection->close();
    }
}
