<?php


namespace App\Providers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\ServiceProvider;



/**
 * Class RepositoryServiceProvider
 * @package App\Providers
 */
class CollectionServiceProvider extends ServiceProvider
{
    /**
     * Register the application repositories
     *
     * @return void
     */
    public function register()
    {

    }



    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
