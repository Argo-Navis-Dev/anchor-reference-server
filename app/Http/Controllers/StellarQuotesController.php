<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use App\Stellar\Sep38Quote\QuotesIntegration;
use ArgoNavis\PhpAnchorSdk\exception\InvalidSep10JwtData;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use ArgoNavis\PhpAnchorSdk\Sep38\Sep38Service;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StellarQuotesController extends Controller
{
    public function quotes(ServerRequestInterface $request): ResponseInterface {

        $auth = $this->getStellarAuthData($request);
        try {
            $sep10Jwt = $auth === null ? null : Sep10Jwt::fromArray($auth);
            $sep38Integration = new QuotesIntegration();
            $sep38Service = new Sep38Service($sep38Integration);
            return $sep38Service->handleRequest($request, $sep10Jwt);
        } catch (InvalidSep10JwtData $e) {
            return new JsonResponse(['error' => 'Unauthorized! Invalid token data: ' . $e->getMessage()], 401);
        }
    }

    /**
     * Extracts the "stellar_auth" data provided by the StellarAuthMiddleware.
     * It represents the data contained in the jwt token.
     * @param ServerRequestInterface $request
     * @return array<array-key | mixed> |null the extracted data if found, otherwise null
     */
    private function getStellarAuthData(ServerRequestInterface $request) : ?array {
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
