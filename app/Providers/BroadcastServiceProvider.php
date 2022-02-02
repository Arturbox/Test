<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // for API auth
        Broadcast::routes(['middleware' => ['auth:client-api', 'cors'], 'prefix' => 'api/v1']);

        // for internal auth
        Broadcast::routes();

        require base_path('routes/channels.php');
    }
}
