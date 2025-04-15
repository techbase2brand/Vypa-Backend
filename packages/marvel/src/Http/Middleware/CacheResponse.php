<?php

namespace Marvel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class CacheResponse
{
    public function handle(Request $request, Closure $next, $ttl = 300)
    {
        // Skip caching for non-GET requests
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Generate cache key based on request
        $cacheKey = $this->getCacheKey($request);

        // Check if response is cached
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Get response
        $response = $next($request);

        // Cache successful responses
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, $response, $ttl);
        }

        return $response;
    }

    protected function getCacheKey(Request $request)
    {
        $route = Route::current();
        $params = $request->all();
        $user = $request->user();

        return sprintf(
            'api_response_%s_%s_%s_%s',
            $route->getName(),
            $user ? $user->id : 'guest',
            md5(json_encode($params)),
            $request->getQueryString()
        );
    }
} 