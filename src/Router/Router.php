<?php

declare(strict_types=1);

namespace Fignon\Router;

use Fignon\TunnelStation\TunnelStation;

/**
 * Fignon Router
 *
 * Once you’ve created a router object, you can add middleware and HTTP
 * method routes (such as get, put, post, and so on) to it just like an application
 */
class Router extends TunnelStation
{
    public function __construct()
    {
        parent::__construct();
    }
}
