<?php

namespace Improntus\PedidosYa\Helper\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    protected $loggerType = Logger::ERROR;

    protected $fileName = '/var/log/error_pedidosya.log';
}