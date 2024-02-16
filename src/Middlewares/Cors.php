<?php

declare(strict_types=1);

namespace Fignon\Middlewares;

/**
 * Enable cross-origin resource sharing (CORS) with various options.
 */
class Cors
{
    public function __invoke($req, $res, $next)
    {
        $res = $res->withHeader('Access-Control-Allow-Origin', '*');
        $res = $res->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization');
        $res = $res->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');

        $next();
    }
}
