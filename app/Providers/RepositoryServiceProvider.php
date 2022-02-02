<?php


namespace App\Providers;

use App\Repositories\ActivityRepository;
use App\Repositories\DisputeMessageRepository;
use App\Repositories\Interfaces\ActivityRepositoryInterface;
use App\Repositories\Interfaces\DisputeMessageRepositoryInterface;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Repositories\Interfaces\OptionsRepositoryInterface;
use App\Repositories\InvoiceRepository;
use App\Repositories\JobRepository;
use App\Repositories\Interfaces\JobRepositoryInterface;
use App\Repositories\Interfaces\JobProposalRepositoryInterface;
use App\Repositories\Interfaces\ScreenShotRepositoryInterface;
use App\Repositories\JobProposalRepository;
use App\Repositories\OptionsRepository;
use App\Repositories\MessageRepository;
use App\Repositories\ScreenShotRepository;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Chat\Interfaces\MsgNotificationServiceInterface;
use App\Services\Chat\MessageNotificationService;
use Illuminate\Support\ServiceProvider;


/**
 * Class RepositoryServiceProvider
 * @package App\Providers
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register the application repositories
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            DisputeMessageRepositoryInterface::class,
            DisputeMessageRepository::class
        );

        $this->app->bind(
            JobRepositoryInterface::class,
            JobRepository::class
        );

        $this->app->bind(
            JobProposalRepositoryInterface::class,
            JobProposalRepository::class
        );

        $this->app->bind(
            ScreenShotRepositoryInterface::class,
            ScreenShotRepository::class
        );

        $this->app->bind(
            ActivityRepositoryInterface::class,
            ActivityRepository::class
        );

        $this->app->bind(
            InvoiceRepositoryInterface::class,
            InvoiceRepository::class
        );

        $this->app->bind(
            MessageRepositoryInterface::class,
            MessageRepository::class
        );

        $this->services();
    }

    public function services()
    {
        $this->app->bind(
            MsgNotificationServiceInterface::class,
            MessageNotificationService::class
        );

        $this->app->bind(
            OptionsRepositoryInterface::class,
            OptionsRepository::class
        );
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
