<?php

namespace App\Providers;

use Illuminate\Auth\EloquentUserProvider;

class CachedUserProvider extends EloquentUserProvider
{
    public function retrieveById($identifier)
    {
        $cacheKey = 'user_'.$identifier;
        return cache()->remember($cacheKey, now()->addHours(8), function () use ($identifier) {
            return parent::retrieveById($identifier);
        });
    }
}