<?php

namespace App\Observers;

use App\OrderExportCronLogs;
use Illuminate\Support\Facades\Log;


/**
 * Class OrderObservers
 * @package App\Observers
 */
trait OrderObservers
{

    /**
     * Boot events first
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        /**
         * Events listen created
         * @param $model
         * @return void
         */
        static::created(function ($model) {
            OrderExportCronLogs::create([
                'code'       => 'created',
                'params'     => $model,
                'message'    => 'TRIGGER OBSERVERS',
                'function'   => __DIR__ . ':' . __FUNCTION__,
            ]);
            return true;
        });

        /**
         * Events listen updated
         * @param $model
         * @return void
         */
        static::updated(function ($model) {
            OrderExportCronLogs::create([
                'code'       => 'updated',
                'params'     => $model,
                'message'    => 'TRIGGER OBSERVERS',
                'function'   => __DIR__ . ':' . __FUNCTION__,
            ]);
            return true;
        });

        //----------
    }
}
