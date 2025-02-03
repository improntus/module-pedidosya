<?php

/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2025 Improntus (http://www.improntus.com/)
 */

namespace Improntus\PedidosYa\Helper\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    protected $loggerType = Logger::ERROR;

    protected $fileName = '/var/log/pedidosya/error.log';
}
