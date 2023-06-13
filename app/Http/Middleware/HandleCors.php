<?php

namespace App\Http\Middleware;

use Closure;
use Asm89\Stack\CorsService;
use Illuminate\Http\Response;

class HandleCors
{
    /**
     * Cors Service
     *
     * @var CorsService
     */
    protected $cors;

    /**
     *
     * @param CorsService $corsService
     */
    public function __construct(CorsService $corsService)
    {
        $this->cors = $corsService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$this->cors->isCorsRequest($request)) {
            return $next($request);
        }

        if ($this->cors->isPreflightRequest($request)) {
            return $this->cors->handlePreflightRequest($request);
        }

        if (!$this->cors->isActualRequestAllowed($request)) {
            return new Response('Not allowed.', 403);
        }

        return $this->cors->addActualRequestHeaders($next($request), $request);
    }
}