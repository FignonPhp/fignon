<?php

declare(strict_types=1);

namespace Fignon\Exception;

class TunnelException extends \Exception
{
    public function __construct($message = "An exception occurred", $code = 1, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
