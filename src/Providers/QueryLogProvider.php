<?php

namespace Litermi\Logs\Providers;

use Litermi\Logs\Services\QueryLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Litermi\Logs\Services\QueryRecordLogService;

/**
 *
 */
class QueryLogProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        DB::listen(static function ($query) {
            QueryLogService::execute($query);
        });

        DB::beforeExecuting(static function ($query, $bindings) {
            QueryRecordLogService::execute($query, $bindings);
        });
    }
}
