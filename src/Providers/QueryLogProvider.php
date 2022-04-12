<?php

namespace Litermi\Logs\Providers;

use Litermi\Logs\Services\SendLogConsoleService;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
                if(config('logs.query_log_is_active') === false){
                   return;
                }

                $queryBinding = '';

                $sql = $query->sql;

                $bindings = array_map(static function ($value) {
                    if ($value instanceof DateTime) {
                        return $value->format('Y-m-d H:i:s');
                    }
                    return $value;
                }, $query->bindings);

                foreach ($bindings as $binding) {
                    $queryBinding .= $binding . ', ';
                    $value        = is_numeric($binding) ? $binding : "'$binding'";
                    $sql          = preg_replace('/\?/', $value, $sql, 1);
                }

                $searchWords = config('logs.exclude_log_query_by_words');
                if (Str::contains($sql, $searchWords)) {
                    return null;
                }

                $arrayQuery     = [
                    'time_query' => $query->time,
                ];
                $sendConsoleLog = new SendLogConsoleService();
                $sendConsoleLog->execute('query complete:' . $sql, $arrayQuery);
            });
    }
}
