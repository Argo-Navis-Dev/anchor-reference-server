<?php

namespace App\Http\Controllers;

use App\Stellar\StellarAppConfig;
use App\Stellar\StellarSep10Config;
use ArgoNavis\PhpAnchorSdk\exception\InvalidConfig;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Service;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StellarAuthController extends Controller
{
    public function auth(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $appConfig = new StellarAppConfig();
            $sep10Config = new StellarSep10Config();
            $sep10Service = new Sep10Service($appConfig, $sep10Config, Log::getLogger());

            return $sep10Service->handleRequest($request, httpClient: new Client());
        } catch (InvalidConfig $invalid) {
            $errorLabel = __(
                'shared_lang.error.internal_server',
                ['error_type' => __('shared_lang.error.invalid_config')]
            );

            return new JsonResponse(['error' => $errorLabel . ' ' . $invalid->getMessage()], 500);
        }
    }
}
