<?php

namespace App\Providers;

use App\Extensions\CacheEloquentProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['auth']->provider('cached-user-driver', 
            function ($app, $config) {
                // use App\Extensions\CacheEloquentProvider;
                return new CacheEloquentProvider(
                    $this->app['hash'], 
                    $config['model']
                );
        });
        $this->registerPolicies();

        // Auth::provider('cached', function ($app, array $config) {
        //     return new CachedUserProvider($app['hash'], $config['model']);
        // });

        //


    }
}
