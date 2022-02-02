<?php

namespace App\Providers;

use App\Enumeration\JobPaymentTypeEnum;
use App\Models\Billing;
use App\Models\Job;
use App\Models\JobProposals;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use App\Services\Job\JobService;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param JobService $jobService
     *
     * @return void
     */
    public function boot(JobService $jobService)
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
