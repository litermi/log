<?php

namespace Litermi\Logs\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
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
    private $type_log = "";

    /**
     * @var
     */
    private bool $tracker = false;

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
    public function tracker(): self
    {
        $this->tracker = true;
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
     * @throws GuzzleException
     */
    public function log($message, $extraValues = []): void
    {
        $this->execute($message, $extraValues);
    }

    /**
     * @param $message
     * @param $extraValues
     * @return void|null
     * @throws GuzzleException
     */
    public function execute($message, $extraValues = []): void
    {

        if ( !is_array($extraValues)) {
            $extraValues = [];
        }
        if ($this->tracker === true && array_key_exists('tracker', $extraValues) === false) {
            $extraValues['tracker'] = GetTrackerService::execute();
        }
        $request = request();
        foreach (config('logs.exclude_log_by_endpoint') as $exclude) {
            $verificationExclude = str_contains($request->getRequestUri(), $exclude);
            if ($verificationExclude) {
                return;
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
        if ($this->type_log === "simple") {
            $values                             = [];
            $values['data_log']['time']         = gmdate("Y-m-d H:i:s");
            $values['data_log']['message']      = $message;
            $values['data_log']['extra_values'] = $extraValues;
            $values                             = $this->getGlobalSpecialValuesFromRequest($request, $values);
            $this->logConsoleDirect($values, $message);
            $this->logRequest($values, $message);
            return;
        }

        $parameters = $request->request->all();
        $headers    = GetAllValuesFromHeaderService::execute($request);
        $headers    = $headers->toArray();
        foreach (config('logs.exclude_parameters_of_request') as $excludeRequest) {
            if (isset($parameters[$excludeRequest])) {
                unset($parameters[$excludeRequest]);
            }
        }

        foreach (config('logs.exclude_parameters_of_header') as $excludeHeader) {
            if (isset($parameters[$excludeHeader])) {
                unset($parameters[$excludeHeader]);
            }
        }

        $origin = array_key_exists('origin', $headers) ? $headers['origin']
            : null;

        $values['data_log']['time']         = gmdate("Y-m-d H:i:s");
        $values['data_log']['message']      = $message;
        $values['data_log']['url']          = $request->getRequestUri();
        $values['data_log']['ip']           = $request->ip();
        $values['data_log']['method']       = $request->method();
        $values['data_log']['from']         = $origin;
        $values['data_log']['env']          = config('app.env');
        $values['data_log']['request_all']  = $parameters;
        $values['data_log']['extra_values'] = $extraValues;
        $values                             = $this->getGlobalSpecialValuesFromRequest($request, $values);

        foreach (config('logs.get_special_values_from_request') as $key => $item) {
            if ($request->$item) {
                $values['data_log'][$key] = $request->$item;
            }
        }

        $this->logConsoleDirect($values, $message);
        $this->logRequest($values, $message);
    }

    /**
     * @param Request $request
     * @param         $values
     */
    private function getGlobalSpecialValuesFromRequest(Request $request, $values): array
    {
        foreach (config('logs.get_global_special_values_from_request') as $key => $item) {
            if ($request->$item) {
                $values['data_log'][$key] = $request->$item;
            }
        }
        return $values;
    }

    /**
     * @param $values
     * @param $message
     */
    private function logConsoleDirect($values, $message): void
    {
        try {
            $string = json_encode($values);
            $string = str_replace("\\", '', $string);
            Log::channel('stderr')->info($string);
            return;
        } catch (Exception $exception) {
        }

        try {
            $string = json_encode($message);
        } catch (Exception $exception) {
            $string = "error encode log";
        }

        $string = str_replace("\\", '', $string);
        Log::channel('stderr')
            ->info($string);
    }

    /**
     * @param $values
     * @param $message
     * @return void
     * @throws GuzzleException
     */
    public function logRequest($values, $message): void
    {
        $nameHeaderSyncJob = config('logs.name_header_sync_job');
        $baseUri           = config('logs.base_uri_send_log_request');
        $requestPath       = config('logs.base_path_send_log_request');
        $typeJob           = request()->header($nameHeaderSyncJob);
        if (empty($nameHeaderSyncJob) === false && empty($typeJob) === false) {
            $client = new Client(
                [
                    'base_uri' => $baseUri,
                    'curl'     => [
                        CURLOPT_SSL_VERIFYPEER => false,
                    ],
                ]
            );

            $method                           = 'POST';
            $values['message']                = $message;
            $formAndHeader                    = ['json' => $values, 'headers' => [],];

            try {
                $client->request($method, $requestPath, $formAndHeader);
            } catch (Exception $exception) {
            }
        }

    }
}
