<?php

namespace Tutu\Withdrawal;

use Illuminate\Support\ServiceProvider;

class WithdrawalServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }

        withdrawal::boot();
    }
}
