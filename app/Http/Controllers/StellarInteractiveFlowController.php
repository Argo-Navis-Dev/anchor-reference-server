<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use App\Stellar\Sep24Interactive\InteractiveFlowIntegration;
use App\Stellar\StellarAppConfig;
use App\Stellar\StellarSep24Config;
use ArgoNavis\PhpAnchorSdk\exception\InvalidSep10JwtData;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use ArgoNavis\PhpAnchorSdk\Sep24\Sep24Service;
use Illuminate\Support\Facades\Log;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StellarInteractiveFlowController extends Controller
{
    public function interactive(ServerRequestInterface $request): ResponseInterface
    {
        $auth = $this->getStellarAuthData($request);
        try {
            $sep10Jwt = $auth === null ? null : Sep10Jwt::fromArray($auth);
            $sep24Config = new StellarSep24Config();
            $sep24Integration = new InteractiveFlowIntegration();
            $sep24Service = new Sep24Service(
                $sep24Config,
                new StellarAppConfig(),
                $sep24Integration,
                Log::getLogger(),
            );

            return $sep24Service->handleRequest($request, $sep10Jwt);
        } catch (InvalidSep10JwtData $e) {
            Log::error(
                'Invalid JWT token.',
                ['context' => 'sep24', 'error' => $e->getMessage(), 'exception' => $e, 'http_status_code' => 401],
            );

            return new JsonResponse(
                ['error' => __(
                    'shared_lang.error.unauthorized.invalid_token',
                    ['exception' => $e->getMessage()],
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
