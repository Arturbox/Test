<?php

namespace App\Providers;

use App\Models\ChatMessage;
use App\Models\Job;
use App\Models\Screenshot;
use App\Models\User;
use App\Observers\ChatObserver;
use App\Observers\JobObserver;
use App\Observers\ScreenshotStatusUpdateObserver;
use App\Observers\UserObserver;
use App\Services\Log\LogService;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ClientRegistered' => [
            'App\Listeners\ClientRegisteredListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        ChatMessage::observe(ChatObserver::class);


        Event::listen([
            'eloquent.created: App*',
            'eloquent.updated: App*',
            'eloquent.deleted: App*',
        ], function ($actionString, $dataTypes) {
            foreach ($dataTypes as $dataType) {
                 LogService::prepareAndAddData($actionString, $dataType);
            }
            return true;
        });

        parent::boot();
    }
}
