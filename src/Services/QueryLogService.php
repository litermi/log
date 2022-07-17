<?php

namespace Litermi\Logs\Services;

use DateTime;
use Illuminate\Support\Str;
use Litermi\Cache\Models\ModelCacheConst;

/**
 *
 */
class QueryLogService
{

    /**
     * @param $query
     * @return void|null
     */
    public static function execute($query)
    {
        if (config('logs.query_log_is_active') === false) {
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

        $queryActive    = request()->header(ModelCacheConst::HEADER_ACTIVE_RECORD);
        $arrayQuery     = [
            'time_query'   => $query->time,
            'query_active' => $queryActive,
        ];
        $sendConsoleLog = new SendLogConsoleService();
        $sendConsoleLog->execute('query complete:' . $sql, $arrayQuery);
    }
}
