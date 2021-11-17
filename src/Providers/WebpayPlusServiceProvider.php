<?php

namespace Crealab\WebpayPlusPaymentGateway\Providers;

use Illuminate\Support\ServiceProvider;
use Crealab\WebpayPlusPaymentGateway\Commands\WebpayStatusCommand;

class WebpayPlusServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $srcPath = $this->getSrcPath();
        $this->mergeConfigFrom(
            $srcPath.'/Config/webpay.php', 'webpay'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                WebpayStatusCommand::class,
            ]);
        }

        $srcPath = $this->getSrcPath();
        $this->loadMigrationsFrom($srcPath.'/Migrations');
        $this->publishes([
            $srcPath.'/Config/webpay.php' => $this->configPath('webpay.php')
        ], 'config');

        $this->publishes([
            $srcPath.'/Migrations/' => database_path('migrations')
        ], 'migrations');
    }


    private function getSrcPath(){
        return dirname( dirname(__FILE__) );
    }

    private function configPath($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }

}
