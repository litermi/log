<?php

namespace Cirelramos\Logs\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 *
 */
class SendLogUserRequestResponseService
{
    /**
     * @param $response
     * @return void
     */
    public static function execute($response): void
    {
        $idUser = Auth::id();
        if (!empty($idUser)) {

            if(is_a($response, \Illuminate\Database\Eloquent\Collection::class)) {
                /** @var Collection $response */
                $response = $response->toArray();
            }

            if(is_a($response, Model::class)) {
                /** @var Model $response */
                $response = $response->toArray();
            }

            if(is_object($response)) {
                /** @var object $response */
                $response = (array) $response;
            }

            $info    = [
                'id_user'  => $idUser,
                'response' => $response,
            ];
            $tag     = "info-{$idUser}-request";

            $sendLogConsoleService = new SendLogConsoleService();
            $sendLogConsoleService->execute(
                $tag,
                $info
            );
        }

    }
}
