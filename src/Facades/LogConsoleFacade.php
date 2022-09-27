<?php

namespace Litermi\Logs\Facades;

use Illuminate\Support\Facades\Facade;
use Litermi\Logs\Services\SendLogConsoleService;

/**
 * @method static SendLogConsoleService simple()
 * @method static SendLogConsoleService full()
 * @method static SendLogConsoleService tracker()
 * @method static SendLogConsoleService typeLog(string $connection)
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
