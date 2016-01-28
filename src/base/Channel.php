<?php

namespace devtransition\rabbitmq\base;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use yii\base\Component;
use yii\helpers\ArrayHelper;


class Channel extends Component
{
    const EXCHANGE_TYPE_TOPIC = 'topic';
    const EXCHANGE_TYPE_DIRECT = 'direct';
    const EXCHANGE_TYPE_HEADERS = 'headers';
    const EXCHANGE_TYPE_FANOUT = 'fanout';

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $amqpChannel;

    /**
     * @inheritdoc
     */
    public function __construct(ConnectionInterface $connection, $config = [])
    {
        $this->connection = $connection;
        $this->amqpChannel = $this->connection->getAmqpInstance()->channel();
        parent::__construct($config);
    }

    public function __destruct()
    {
        $this->amqpChannel->close();
    }

    /**
     * Server should continue sending data to us. Default on new channels.
     */
    public function enableFlow()
    {
        $this->amqpChannel->flow(true);
    }

    /**
     * Server should stop sending data to us.
     */
    public function disableFlow()
    {
        $this->amqpChannel->flow(true);
    }

    public function send()
    {
        $msg = new AMQPMessage('test');
        $this->amqpChannel->basic_publish($msg, '', 'test');
    }

    public function getMessageOne($queue, $no_ack = false)
    {
        $msg = $this->amqpChannel->basic_get($queue, $no_ack = false);
        if ($msg instanceof AMQPMessage) {
            $res = new AMQPMessage();
            return $res;
        }

        throw new InvalidValueException("response was not a valid amqp message object");
    }

    public function getMessageConsume($queue, Callable $callback, Array $options = [])
    {
        return $this->amqpChannel->basic_consume(
            $queue = '',
            $consumer_tag = ArrayHelper::getValue($options, 'consumer_tag', ''),
            $no_local = ArrayHelper::getValue($options, 'no_local', false),
            $no_ack = ArrayHelper::getValue($options, 'no_ack', false),
            $exclusive = ArrayHelper::getValue($options, 'exclusive', false),
            $nowait = ArrayHelper::getValue($options, 'nowait', false),
            $callback,
            $ticket = null,
            $arguments = array()
        );

        /*
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        */

    }

    public function declareExchange($name, $type, $passive = false, $durable = false, $auto_delete = true, $internal = false)
    {
        return $this->amqpChannel->exchange_declare($name, $type, $passive, $durable, $auto_delete, $internal);
    }

    public function deleteExchange($name, $if_unused = false, $if_empty = false, $nowait = false)
    {
        return $this->amqpChannel->exchange_delete($name, $if_unused, $if_empty, $nowait);
    }

    public function declareQueue($name, $passive = false, $durable = false, $auto_delete = true, $internal = false)
    {
        return $this->amqpChannel->queue_declare($name, $passive, $durable, $auto_delete, $internal);
    }

    public function deleteQueue($name, $if_unused = false, $if_empty = false, $nowait = false)
    {
        return $this->amqpChannel->queue_delete($name, $if_unused, $if_empty, $nowait);
    }

    public function purgeQueue($name, $nowait = false)
    {
        return $this->amqpChannel->queue_purge($name, $nowait);
    }

    public function bindQueueExchange($queue, $exchange, $routing_key)
    {
        return $this->amqpChannel->queue_bind($queue, $exchange, $routing_key);
    }
}
