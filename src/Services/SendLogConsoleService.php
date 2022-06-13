<?php

namespace Litermi\Logs\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 *
 */
class SendLogConsoleService
{

    /**
     * @var
     */
    public $type_log = "";

    public function __construct()
    {
        $this->type_log = "full";
    }

    /**
     * @return $this
     */
    public function simple(): self
    {
        $this->type_log = "simple";
        return $this;
    }

    /**
     * @return $this
     */
    public function full(): self
    {
        $this->type_log = "full";
        return $this;
    }

    /**
     * @return $this
     */
    public function typeLog(string $typeLog): self
    {
        $this->type_log = $typeLog;
        return $this;
    }

    /**
     * @param $message
     * @param $extraValues
     * @return void|null
     */
    public function log($message, $extraValues = []){
      return $this->execute($message, $extraValues);
    }

    /**
     * @param $message
     * @param $extraValues
     * @return void|null
     */
    public function execute($message, $extraValues = [])
    {
        if ( !is_array($extraValues)) {
            $extraValues = [];
        }
        $request = request();
        foreach (config('logs.exclude_log_by_endpoint') as $exclude) {
            $verificationExclude = str_contains($request->getRequestUri(), $exclude);
            if ($verificationExclude) {
                return null;
            }
        }

        $this->getBasicInfoLog($request, $message, $extraValues);
    }

    /**
     * @param Request $request
     * @param         $message
     * @param         $extraValues
     */
    private function getBasicInfoLog(Request $request, $message, $extraValues): void
    {

        if($this->type_log === "simple"){
            $values = [];
            $values[ 'data_log' ][ 'time' ]         = gmdate("Y-m-d H:i:s");
            $values[ 'data_log' ][ 'message' ]      = $message;
            $values[ 'data_log' ][ 'extra_values' ] = $extraValues;
            $this->logConsoleDirect($values);
            return;
        }

        $parameters = $request->request->all();
        $headers    = GetAllValuesFromHeaderService::execute($request);
        $headers    = $headers->toArray();
        foreach (config('logs.exclude_parameters_of_request') as $excludeRequest) {
            if (isset($parameters[ $excludeRequest ])) {
                unset($parameters[ $excludeRequest ]);
            }
        }

        foreach (config('logs.exclude_parameters_of_header') as $excludeHeader) {
            if (isset($parameters[ $excludeHeader ])) {
                unset($parameters[ $excludeHeader ]);
            }
        }

        $origin = array_key_exists('origin', $headers) ? $headers[ 'origin' ]
            : null;

        $values[ 'data_log' ][ 'time' ]         = gmdate("Y-m-d H:i:s");
        $values[ 'data_log' ][ 'message' ]      = $message;
        $values[ 'data_log' ][ 'url' ]          = $request->getRequestUri();
        $values[ 'data_log' ][ 'ip' ]           = $request->ip();
        $values[ 'data_log' ][ 'method' ]       = $request->method();
        $values[ 'data_log' ][ 'from' ]         = $origin;
        $values[ 'data_log' ][ 'env' ]          = config('app.env');
        $values[ 'data_log' ][ 'request_all' ]  = $parameters;
        $values[ 'data_log' ][ 'extra_values' ] = $extraValues;

        foreach (config('logs.get_special_values_from_request') as $key => $item) {
            if ($request->$item) {
                $values[ 'data_log' ][ $key ] = $request->$item;
            }
        }

        $this->logConsoleDirect($values);
    }

    /**
     * @param $values
     */
    private function logConsoleDirect($values): void
    {
        $string = "";
        try {
            $string = json_encode($values);
        }
        catch(Exception $exception) {
            $string = "error encode log";
        }
        $string = str_replace("\\", '', $string);
        Log::channel('stderr')
            ->info($string);
    }
}
