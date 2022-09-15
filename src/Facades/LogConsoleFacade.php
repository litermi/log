<?php

namespace Litermi\Logs\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static self simple()
 * @method static self full()
 * @method static self tracker()
 * @method static self typeLog(string $connection)
 * @method static void log(string $message, array $extraValues = [])
 *
 */
class LogConsoleFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'send-log-console-service';
    }
}
