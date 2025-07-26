<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;
use Laravel\Octane\Events\RequestReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Octane\Octane;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // config(['app.locale' => 'id']);
	    // Carbon::setLocale('id');
        
        JsonResource::withoutWrapping();

        
        // app('db')->listen(function($query) {
        //     app('log')->info(
        //         $query->sql,
        //         $query->bindings,
        //         $query->time
        //     );
        // });

        /**
         * Paginate a standard Laravel Collection.
         *
         * @param int $perPage
         * @param int $total
         * @param int $page
         * @param string $pageName
         * @return array
         */

        // Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
        //     $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

        //     return new LengthAwarePaginator(
        //         $total ? $this : $this->forPage($page, $perPage)->values(),
        //         $total ?: $this->count(),
        //         $perPage,
        //         $page,
        //         [
        //             'path' => LengthAwarePaginator::resolveCurrentPath(),
        //             'pageName' => $pageName,
        //         ]
        //     );
        // });


        // if (config('octane.server')) {
        //     Event::listen(RequestReceived::class, function () {
        //         try {
        //             // Cek apakah koneksi MySQL masih hidup
        //             DB::connection()->getPdo()->query('SELECT 1');
        //         } catch (\Exception $e) {
        //             // Log ketika koneksi MySQL mati
        //             Log::warning('MySQL connection lost, reconnecting...');
    
        //             // Purge dan reconnect
        //             DB::purge('mysql');
        //             DB::reconnect('mysql');
    
        //             // Log setelah reconnect berhasil
        //             Log::info('MySQL connection re-established.');
        //         }
        //     });
        // }
    }
}
