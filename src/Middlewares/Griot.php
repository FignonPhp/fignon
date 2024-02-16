<?php
declare(strict_types=1);

namespace Fignon\Middlewares;

use Fignon\Extra\Terminal;


class Griot
{
    public function __invoke($req, $res, $next)
    {
        $this->log($req, $res, "✅ : " . $req->method . " : " . $req->originalUrl);        
        $next();
    }

    public static function log($req, $res, $message)
    {
        (new Terminal())
            ->message($message)
            ->setColor('green')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }

    public static function error($req, $res, $message)
    {
        (new Terminal())
            ->message("❌ : " . $req->method . " : " . $req->originalUrl . " - " . $message)
            ->setColor('red')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }

    public static function warn($req, $res, $message)
    {
        (new Terminal())
            ->message("⚠️ : " . $req->method . " : " . $req->originalUrl . " - " . $message)
            ->setColor('yellow')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }

    public static function info($req, $res, $message)
    {
        (new Terminal())
            ->message("🚦 : " . $req->method . " : " . $req->originalUrl . " - " . $message)
            ->setColor('blue')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }

    public static function debug($req, $res, $message)
    {
        (new Terminal())
            ->message("🐞 : " . $req->method . " : " . $req->originalUrl . " - " . $message)
            ->setColor('cyan')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }

    public static function trace($req, $res, $message)
    {
        (new Terminal())
            ->message("🔍 : " . $req->method . " : " . $req->originalUrl . " - " . $message)
            ->setColor('magenta')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }

    public static function fatal($req, $res, $message)
    {
        (new Terminal())
            ->message("💀 " . $req->method . " : " . $req->originalUrl . " - " . $message)
            ->setColor('red')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }

    public static function success($req, $res, $message)
    {
        (new Terminal())
            ->message("✅ : " . $req->method . " : " . $req->originalUrl . " - " . $message)
            ->setColor('green')
            ->setStyle('bold')
            ->setFrame()
            ->send();
    }
}
