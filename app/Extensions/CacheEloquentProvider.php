<?php
// Place the class wherever you think is good
namespace App\Extensions; 

use Illuminate\Auth\EloquentUserProvider;

class CacheEloquentProvider extends EloquentUserProvider
{
    public function retrieveById ($identifier)
    {
        // implement cache however you like
        // following is for simplicity.
        // $user = app('cache')->get($identifier);
        // if (!$user) {
        //     $user = parent::retrieveById($identifier);
        //     if ($user) {
        //         app('cache')->put($identifier, $user);
        //     }
        // }
        
        // return $user;
        $cacheKey = 'user_'.$identifier;
        return cache()->remember($cacheKey, now()->addHours(8), function () use ($identifier) {
            return parent::retrieveById($identifier);
        });
    }
}