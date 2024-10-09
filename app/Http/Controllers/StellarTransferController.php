<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use App\Stellar\Sep06Transfer\TransferIntegration;
use App\Stellar\Sep38Quote\QuotesIntegration;
use App\Stellar\StellarAppConfig;
use App\Stellar\StellarSep06Config;
use ArgoNavis\PhpAnchorSdk\exception\InvalidSep10JwtData;
use ArgoNavis\PhpAnchorSdk\Sep06\Sep06Service;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use Illuminate\Support\Facades\Log;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StellarTransferController extends Controller
{
    public function transfer(ServerRequestInterface $request): ResponseInterface
    {

        $auth = $this->getStellarAuthData($request);
        try {
            $sep10Jwt = $auth === null ? null : Sep10Jwt::fromArray($auth);
            $sep06Service = new Sep06Service(
                appConfig: new StellarAppConfig(),
                sep06Config: new StellarSep06Config(),
                sep06Integration: new TransferIntegration(),
                quotesIntegration: new QuotesIntegration(),
                logger: Log::getLogger()
            );

            return $sep06Service->handleRequest($request, $sep10Jwt);
        } catch (InvalidSep10JwtData $e) {
            return new JsonResponse(
                ['error' => __(
                    'shared_lang.error.unauthorized.invalid_token',
                    ['exception' => $e->getMessage()]
                )],
                401
            );
        }
    }


    /**
     * Extracts the "stellar_auth" data provided by the StellarAuthMiddleware.
     * It represents the data contained in the jwt token.
     * @param ServerRequestInterface $request
     * @return array<array-key | mixed> |null the extracted data if found, otherwise null
     */
    private function getStellarAuthData(ServerRequestInterface $request) : ?array
    {
        $authDataKey = 'stellar_auth';
        $params = $request->getQueryParams();
        if (isset($params[$authDataKey])) {
            return $params[$authDataKey];
        }
        $params = $request->getParsedBody();
        if (isset($params[$authDataKey])) {
            return $params[$authDataKey];
        }
        return null;
    }
}
