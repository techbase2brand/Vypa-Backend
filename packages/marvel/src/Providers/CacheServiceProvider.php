<?php

namespace Marvel\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Marvel\Database\Models\Order;
use Marvel\Database\Models\Settings;
use Marvel\Database\Models\Product;
use Marvel\Database\Models\User;

class CacheServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Register cache observers
        $this->registerCacheObservers();
    }

    protected function registerCacheObservers()
    {
        // Order cache management
        Order::created(function ($order) {
            Cache::tags(['orders'])->flush();
        });

        Order::updated(function ($order) {
            Cache::tags(['orders'])->flush();
            Cache::forget('order_' . $order->id);
            Cache::forget('order_' . $order->tracking_number);
        });

        Order::deleted(function ($order) {
            Cache::tags(['orders'])->flush();
            Cache::forget('order_' . $order->id);
            Cache::forget('order_' . $order->tracking_number);
        });

        // Settings cache management
        Settings::updated(function ($settings) {
            Cache::forget('settings');
        });

        // Product cache management
        Product::created(function ($product) {
            Cache::tags(['products'])->flush();
        });

        Product::updated(function ($product) {
            Cache::tags(['products'])->flush();
        });

        Product::deleted(function ($product) {
            Cache::tags(['products'])->flush();
        });

        // User cache management
        User::updated(function ($user) {
            Cache::tags(['users'])->flush();
        });
    }

    public function register()
    {
        // Register cache macros
        $this->registerCacheMacros();
    }

    protected function registerCacheMacros()
    {
        Cache::macro('rememberWithTags', function ($key, $tags, $ttl, $callback) {
            return Cache::tags($tags)->remember($key, $ttl, $callback);
        });

        Cache::macro('forgetWithTags', function ($key, $tags) {
            return Cache::tags($tags)->forget($key);
        });
    }
} 