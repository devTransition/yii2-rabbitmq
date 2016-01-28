<?php

namespace devtransition\rabbitmq\base;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use yii\base\Component;


/**
 * Connection represents an advanced instance of PhpAmqpLibs AMQPStreamConnection.
 *
 * Connection works together with Channels .... explain..
 *
 * @property string $host RabbitMQ server hostname or IP address. Defaults to `localhost`.
 * @property integer $port TCP-Port where RabbitMQ is listening. Defaults to `5672`.
 * @property string $username RabbitMQ server username on connect. Defaults to `guest`.
 * @property string $password RabbitMQ server password on connect. Defaults to `guest`.
 * @property string $vhost RabbitMQ server virtual host to use. Defaults to `/`.
 * @property \PhpAmqpLib\Connection\AMQPStreamConnection $connection The PhpAmqpLib connection instance. This property is read-only.
 *
 * @author Nicolas Wild <nwild79@gmail.com>
 */
class Connection extends Component implements ConnectionInterface
{
    /**
     * @var AMQPStreamConnection
     */
    public $connection;
    /**
     * @var string
     */
    public $host = 'localhost';
    /**
     * @var integer
     */
    public $port = 5672;
    /**
     * @var string
     */
    public $username = 'guest';
    /**
     * @var string
     */
    public $password = '';
    /**
     * @var string
     */
    public $vhost = '/';
    /**
     * @var array
     */
    public $options = [];
    /**
     * @var AMQPChannel[]
     */
    protected $channels = [];

    /**
     * @var AMQPStreamConnection
     */
    protected $amqpConnection;
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->_parseOptions();
        $this->open();
    }

    public function open()
    {
        /* TODO: Implement options appy and TLS */

        $this->amqpConnection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->username,
            $this->password,
            $this->vhost,
            $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            $connection_timeout = 3,
            $read_write_timeout = 3,
            $context = null,
            $keepalive = false,
            $heartbeat = 0
        );
    }

    public function close()
    {
        $this->amqpConnection->close();
    }

    public function reconnect()
    {
        $this->amqpConnection-> reconnect();
    }

    public function isConnected()
    {
        $this->amqpConnection->isConnected();
    }

    /**
     * Returns low-level AMQP connection instance
     *
     * @return AMQPStreamConnection|AMQPSSLConnection
     */
    public function getAmqpInstance()
    {
        return $this->amqpConnection;
    }


    private function _parseOptions()
    {
        /* TODO: Implement */
    }
}
