<?php

declare(strict_types=1);

namespace Fignon\Error;

class TunnelError extends \Error
{
    public function __construct($message = "An error occurred", $code = 1, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
