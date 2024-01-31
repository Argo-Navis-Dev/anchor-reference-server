<?php

declare(strict_types=1);

// Copyright 2023 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use App\Stellar\Sep12Customer\CustomerIntegration;
use ArgoNavis\PhpAnchorSdk\exception\InvalidSep10JwtData;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use ArgoNavis\PhpAnchorSdk\Sep12\Sep12Service;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class StellarCustomerController extends Controller
{
    public function customer(ServerRequestInterface $request): ResponseInterface {

        $auth = $this->getStellarAuthData($request);
        if ($auth === null) {
            return new JsonResponse(['error' => 'Unauthorized! Use SEP-10 to authorize.'], 401);
        }
        try {
            $sep10Jwt = Sep10Jwt::fromArray($auth);
            $customerIntegration = new CustomerIntegration();
            $sep12Service = new Sep12Service($customerIntegration);
            return $sep12Service->handleRequest($request, $sep10Jwt);
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
