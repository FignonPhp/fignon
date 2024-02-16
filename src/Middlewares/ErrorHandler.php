<?php
declare(strict_types=1);

namespace Fignon\Middlewares;

use Fignon\Response\Response;
use Fignon\Request\Request;

/**
 * Built in error handler of Fignon.
 * Handle 404 and 500 errors.
 */
class ErrorHandler
{

    public static function NotFound(Request $req, Response $res, mixed $next)
    {
        $message = "Cannot  ". strtoupper($req->method) . ' ' . $req->path;
        $res
            ->status(404)
            ->html("<!doctype html><html class='h-full'><head><title>404 - Page not found</title><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><script src='https://cdn.tailwindcss.com'></script></head><body class='h-full'><main class='grid min-h-full place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8'><div class='text-center'><p class='text-base font-semibold text-indigo-600'>⛔️ 404</p><h1 class='mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl'>Page not found</h1><small class='mt-1 text-sm font-semibold text-gray-600'>$message</small><p class='mt-6 text-base leading-7 text-gray-600'>Sorry, we couldn’t find the page you’re looking for.</p><div class='mt-10 flex items-center justify-center gap-x-6'><a href='/' class='rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'>Go back home</a><a href='#' class='text-sm font-semibold text-gray-900'>Contact support<span aria-hidden='true'>&rarr;</span></a></div></div><footer class='absolute bottom-4 w-full text-center text-gray-400'><p class='text-sm'>Powered by Fignon.</p></footer></main></body></html>");
    }

    public static function UnHandledError(\Error $err, Request $req, Response $res, mixed $next)
    {
        if ($req->app->config('env') === 'development' || $req->app->config('env') === 'test' || $req->app->config('debug') === true) {
         
            $message = "Cannot " . strtoupper($req->method) . ' ' . $req->path . ' <br>' . "<strong class='text-center '> Because Of: " . $err->getMessage() . "</strong> At: <p class='mt-6 text-base leading-7 text-gray-600'> File: " . $err->getFile() . ' at line: ' . $err->getLine() . '</p> <br> Trace: <br><pre class=text-wrap>' . $err->getTraceAsString() . "</pre>";
            $res
                ->status(500)
                ->html("<!doctype html><html class='h-full'><head><title>500 - Internal Server Error</title><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><script src='https://cdn.tailwindcss.com'></script></head><body class='h-full flex items-center justify-center overflow-hidden'><main class='grid min-h-full place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8' ><div class='text-center'><p class='text-base font-semibold text-indigo-600'>⛔️ 500</p><h1 class='mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl'>Internal Server Error</h1><div class='mt-1 text-sm font-semibold text-red-600'>$message</div><p class='mt-6 text-base leading-7 text-gray-600'>Sorry, we couldn’t process your request.</p><div class='mt-10 flex items-center justify-center gap-x-6'><a href='/' class='rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'>Go back home</a><a href='#' class='text-sm font-semibold text-gray-900'>Contact support<span aria-hidden='true'>&rarr;</span></a></div></div><footer class='absolute bottom-4 w-full text-center text-gray-400'><p class='text-sm'>Powered by Fignon.</p></footer></main></body></html>");
        } else {
            $message = "Cannot " . strtoupper($req->method) . ' ' . $req->path . ' <br>' . "50X - Fatal Error";
           
            $res
                ->status(500)
                ->html("<!doctype html><html class='h-full'><head><title>500 - Internal Server Error</title><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'><script src='https://cdn.tailwindcss.com'></script></head><body class='h-full'><main class='grid min-h-full place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8'><div class='text-center'><p class='text-base font-semibold text-indigo-600'>⛔️ 500</p><h1 class='mt-4 text-3xl font-bold tracking-tight text-gray-900 sm:text-5xl'>Internal Server Error</h1><small class='mt-1 text-sm font-semibold text-gray-600'>$message</small><p class='mt-6 text-base leading-7 text-gray-600'>Sorry, we couldn’t process your request.</p><div class='mt-10 flex items-center justify-center gap-x-6'><a href='/' class='rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'>Go back home</a><a href='#' class='text-sm font-semibold text-gray-900'>Contact support<span aria-hidden='true'>&rarr;</span></a></div></div><footer class='absolute bottom-4 w-full text-center text-gray-400'><p class='text-sm'>Powered by Fignon.</p></footer></main></body></html>");
        }
    }
}
