<?php

declare(strict_types=1);

namespace Fignon\Middlewares;

use Fignon\Request\Request;
use Fignon\Response\Response;

/**
 * Parse incoming request bodies in a middleware
 *
 * before your handlers, available under the $req->body property.
 */
class BodyParser
{
    public function __invoke(Request $req, Response $res, mixed $next)
    {
        $contentType = $req->headers->get('content-type');

        if (!$contentType) {
            $next(); // No Content-Type header, no body parser
        }

        // Parse the Content-Type header
        $parts = explode(';', $contentType);
        $type = trim($parts[0]);

        if ($type === 'application/json') {
            $req->body = json_decode($req->getContent(), true);
        } elseif ($type === 'application/x-www-form-urlencoded') {
            parse_str($req->getContent(), $req->body);
        } elseif ($type === 'multipart/form-data') {
            $body = [];
            foreach ($req->request as $key => $value) {
                $body[$key] = $value;
            }
            $req->body = $body;
        } else {
            return $req->body = $req->getContent();
        }

        $next();
    }
}
