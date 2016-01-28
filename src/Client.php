<?php

namespace devtransition\rabbitmq;

use yii\base\Component;
use yii\base\InvalidParamException;
use yii\di\Container;
use devtransition\rabbitmq\base\Connection;
use devtransition\rabbitmq\base\Channel;

/**
 * Connection represents an advanced instance of PhpAmqpLibs AMQPStreamConnection.
 *
 * Connection works together with Channels .... explain..
 *
 * ```php
 * 'components' => [
 *     'rabbit' => [
 *         'class' => '\devtransition\rabbitmq\Client',
 *         'username' => 'guest',
 *         'password' => 'guest',
 *         'vhost' => '/',
 *     ],
 * ],
 * ```
 *
 * @property Connection $connection The PhpAmqpLib connection instance. This property is read-only.
 *
 * @author Nicolas Wild <nwild79@gmail.com>
 */
class Client extends Component
{
    /**
     * @var string RabbitMQ server hostname or IP address. Defaults to `localhost`.
     */
    public $host = 'localhost';
    /**
     * @var integer TCP-Port where RabbitMQ is listening. Defaults to `5672`.
     */
    public $port = 5672;
    /**
     * @var string RabbitMQ server username on connect. Defaults to `guest`.
     */
    public $username = 'guest';
    /**
     * @var string RabbitMQ server password on connect. Defaults to `guest`.
     */
    public $password = '';
    /**
     * @var string RabbitMQ server virtual host to use. Defaults to `/`.
     */
    public $vhost = '/';
    /**
     * @var array
     */
    public $options = [];
    /**
     * @var Connection|array|string The Connection object or the application component ID of the Connection object.
     * It´s also possible to set an array with class and addition configuration - in that case a new objects gets generated an configured.
     */
    public $connectionClass;
    /**
     * @var array|string The Channel class name or an array with class and addition configuration - in that case a new objects gets generated an configured.
     */
    public $channelClass;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Container DI container holding connection (singleton) and all generated channels
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->container = new Container();

        // Add connection
        $config = [
            'host' => $this->host,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'vhost' => $this->vhost,
        ];

        if (is_array($this->connectionClass)) {
            $connection = array_merge($config, $this->connectionClass);
        } else {
            $connection = array_merge($config, [
                'class' => (!empty($this->connectionClass) ? $this->connectionClass : 'devtransition\rabbitmq\base\Connection'),
            ]);
        }

        $this->container->setSingleton('devtransition\rabbitmq\base\ConnectionInterface', $connection);

        // Define base channel defintion
        $this->container->set("channel", ($this->channelClass ? $this->channelClass : 'devtransition\rabbitmq\base\Channel'));
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->container->get('devtransition\rabbitmq\base\ConnectionInterface');
    }

    /**
     * Returns channel object.
     *
     * All further actions like sending and receiving can be done in the context of that channel.
     *
     * @param string $channel_id user defined name to identify channel
     * @return Channel
     */
    public function getChannel($channel_id = 'default')
    {
        $index = "channel_" . $channel_id;

        if (!$this->container->has($index)) {
            $this->container->set($index, 'channel');
        }

        return $this->container->get($index);
    }

    /**
     * Close an existing channel
     *
     * @param string $channel_id user given name during getChanncel()
     */
    public function closeChannel($channel_id = 'default')
    {
        $index = "channel_" . $channel_id;
        if (!$this->container->has($index)) {
            throw new InvalidParamException('invalid channel id');
        }

        $this->container->clear($index);
    }



}
