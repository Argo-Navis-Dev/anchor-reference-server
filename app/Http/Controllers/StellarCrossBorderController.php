<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use App\Stellar\Sep12Customer\CustomerIntegration;
use App\Stellar\Sep31CrossBorder\CrossBorderIntegration;
use App\Stellar\Sep38Quote\QuotesIntegration;
use ArgoNavis\PhpAnchorSdk\exception\InvalidSep10JwtData;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use ArgoNavis\PhpAnchorSdk\Sep12\Sep12Service;
use ArgoNavis\PhpAnchorSdk\Sep31\Sep31Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StellarCrossBorderController extends Controller
{

    public function cross(ServerRequestInterface $request): ResponseInterface
    {
        $auth = $this->getStellarAuthData($request);
        if ($auth === null) {
            Log::error(
                'The stellar_auth missing from the request, authentication required.',
                ['context' => 'sep31', 'http_status_code' => 401],
            );

            return new JsonResponse(
                ['error' => __('shared_lang.error.unauthorized.missing_stellar_auth')],
                401
            );
        }
        try {
            $sep10Jwt = Sep10Jwt::fromArray($auth);
            $crossBorderIntegration = new CrossBorderIntegration();
            $quotesIntegration = new QuotesIntegration();
            $sep31Service = new Sep31Service(
                sep31Integration: $crossBorderIntegration,
                quotesIntegration: $quotesIntegration,
                logger: Log::getLogger()
            );

            return $sep31Service->handleRequest($request, $sep10Jwt);
        } catch (InvalidSep10JwtData $e) {
            Log::error(
                'Invalid JWT token.',
                ['context' => 'sep31', 'error' => $e->getMessage(), 'exception' => $e, 'http_status_code' => 401],
            );

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
     *
     * @param ServerRequestInterface $request
     *
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
