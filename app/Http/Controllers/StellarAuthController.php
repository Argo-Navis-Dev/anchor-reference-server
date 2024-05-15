<?php

namespace App\Http\Controllers;

use App\Stellar\StellarAppConfig;
use App\Stellar\StellarSep10Config;
use ArgoNavis\PhpAnchorSdk\exception\InvalidConfig;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Service;
use GuzzleHttp\Client;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StellarAuthController extends Controller
{
    public function auth(ServerRequestInterface $request): ResponseInterface {
        try {
            $appConfig = new StellarAppConfig();
            $sep10Config = new StellarSep10Config();
            $sep10Service = new Sep10Service($appConfig, $sep10Config);

            return $sep10Service->handleRequest($request, httpClient: new Client());
        } catch (InvalidConfig $invalid) {
            return new JsonResponse(['error' => 'Internal server error: Invalid config. ' .
                $invalid->getMessage()], 500);
        }
    }
}
