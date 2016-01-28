<?php

namespace devtransition\rabbitmq\base;



use yii\helpers\VarDumper;

class OtherConnection extends Connection  implements ConnectionInterface
{

    public $value;

    public $other = true;


}
