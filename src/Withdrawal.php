<?php

namespace Encore\Admin;

use Encore\Admin\Admin;
use Encore\Admin\Extension;

class Withdrawal extends Extension
{
    /**
     * Load configure into laravel from database.
     *
     * @return void
     */
    public static function load()
    {
        foreach (WithdrawalModel::all(['name', 'value']) as $config) {
            config([$config['name'] => $config['value']]);
        }
    }

    /**
     * Bootstrap this package.
     *
     * @return void
     */
    public static function boot()
    {
        static::registerRoutes();

        Admin::extend('withdrawal', __CLASS__);
    }

    /**
     * Register routes for laravel-admin.
     *
     * @return void
     */
    protected static function registerRoutes()
    {
        parent::routes(function ($router) {
            /* @var \Illuminate\Routing\Router $router */
            $router->get('withdrawal','Encore\Admin\Controller\Withdrawal\IndexController@index');
        });
    }

    /**
     * {@inheritdoc}
     */
    public static function import()
    {
        parent::createMenu('Withdrawal', 'withdrawal', 'fa-toggle-on');

        parent::createPermission('Admin withdrawal', 'ext.Withdrawal', 'Withdrawal*');
    }
}
