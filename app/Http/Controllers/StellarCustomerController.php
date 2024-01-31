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

class StellarCustomerController extends Controller
{
    public function customer(Request $request): ResponseInterface {

        $auth = $request->input('stellar_auth');
        if ($auth === null) {
            return new JsonResponse(['error' => 'Unauthorized! Use SEP-10 to authorize.'], 401);
        }
        try {
            $sep10Jwt = Sep10Jwt::fromArray($auth);
            $customerIntegration = new CustomerIntegration();
            $sep12Service = new Sep12Service($customerIntegration);
            $psrRequest = ServerRequestFactory::fromGlobals();
            return $sep12Service->handleRequest($psrRequest, $sep10Jwt);
        } catch (InvalidSep10JwtData $e) {
            return new JsonResponse(['error' => 'Unauthorized! Invalid token data: ' . $e->getMessage()], 401);
        }
    }
}
