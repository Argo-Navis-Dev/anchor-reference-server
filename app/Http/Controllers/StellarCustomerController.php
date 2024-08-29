<?php

declare(strict_types=1);

// Copyright 2023 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use App\Models\Sep12ProvidedField;
use App\Stellar\Sep12Customer\CustomerIntegration;
use ArgoNavis\PhpAnchorSdk\exception\InvalidSep10JwtData;
use ArgoNavis\PhpAnchorSdk\Sep10\Sep10Jwt;
use ArgoNavis\PhpAnchorSdk\Sep12\Sep12Service;
use Illuminate\Support\Facades\Log;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class StellarCustomerController extends Controller
{
    public function customer(ServerRequestInterface $request): ResponseInterface {

        $auth = $this->getStellarAuthData($request);
        if ($auth === null) {
            return new JsonResponse(
                ['error' => __('shared_lang.error.unauthorized.missing_stellar_auth')],
                401
            );
        }
        try {
            $sep10Jwt = Sep10Jwt::fromArray($auth);
            $customerIntegration = new CustomerIntegration();
            $sep12Service = new Sep12Service($customerIntegration);
            return $sep12Service->handleRequest($request, $sep10Jwt);
        } catch (InvalidSep10JwtData $e) {
            return new JsonResponse(
                ['error' => 'Unauthorized! Invalid token data: ' . $e->getMessage()],
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

    /**
     * Retrieves the passed customer binary (image) field or a dummy image if it does not exist.
     *
     * @param string $id The ID of the customer.
     * @param int $providedFieldID The ID of the image field.
     * @return BinaryFileResponse
     */
    public function renderBinaryField(string $id, int $providedFieldID): Response
    {
        LOG::debug('Loading image field: ' . $providedFieldID . ' by customer: ' . $id);
        $imgField = Sep12ProvidedField::where('sep12_customer_id', $id)
            ->where('id', $providedFieldID)
            ->first();
        if ($imgField && $imgField->binary_value) {
            $size = strlen($imgField->binary_value);
            if ($size == 0) {
                LOG::debug('The image field is empty!');
                return response()->file(public_path('img/empty.jpg'));
            }
            $mimeType = finfo_buffer(finfo_open(), $imgField->binary_value, FILEINFO_MIME_TYPE);
            LOG::debug('The image field has been found, the mime type is: ' . $mimeType);
            return response($imgField->binary_value)->header('Content-Type', $mimeType);
        }
        LOG::debug('The image field has not been found!');
        return response()->file(public_path('img/empty.jpg'));
    }
}
