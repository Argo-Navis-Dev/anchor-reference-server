<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep12Customer;

use App\Models\Sep12Customer;
use App\Models\Sep12ProvidedField;
use ArgoNavis\PhpAnchorSdk\callback\GetCustomerRequest;
use ArgoNavis\PhpAnchorSdk\callback\GetCustomerResponse;
use ArgoNavis\PhpAnchorSdk\callback\ICustomerIntegration;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerRequest;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerResponse;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerVerificationRequest;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\CustomerNotFoundForId;
use Illuminate\Support\Facades\Log;

class CustomerIntegration implements ICustomerIntegration
{
    private string $id = 'd1ce2f48-3ff1-495d-9240-7a50d806cfed';

    /**
     * @inheritDoc
     */
    public function getCustomer(GetCustomerRequest $request): GetCustomerResponse
    {
        $customer = null;

        if ($request->id != null) {
            $customer = Sep12Customer::where('id', $request->id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId($request->id);
            }
        } else if ($request->account != null) {
            $customer = Sep12Helper::getSep12CustomerByAccountId($request->account,
                $request->memo, $request->type);
        }

        return Sep12Helper::buildCustomerResponse($customer);
    }

    /**
     * @inheritDoc
     */
    public function putCustomer(PutCustomerRequest $request): PutCustomerResponse
    {
        $account = $request->account;
        $id = $request->id;

        if ($account === null && $id === null) {
            throw new AnchorFailure('missing id or account');
        }
        $customer = null;

        if ($request->id != null) {
            $customer = Sep12Customer::where('id', $request->id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId($request->id);
            }
        } else if ($request->account != null) {
            $customer = Sep12Helper::getSep12CustomerByAccountId($request->account,
                $request->memo, $request->type);
        }

        if ($customer === null) {
            // new customer
            $customer = Sep12Helper::newSep12Customer($request);
        } else {
            // update customer
            $customer = Sep12Helper::updateSep12Customer($customer, $request);
        }

        return new PutCustomerResponse(id: $customer->id);
    }

    /**
     * @inheritDoc
     */
    public function putCustomerVerification(PutCustomerVerificationRequest $request): GetCustomerResponse
    {
        // TODO implement this.

        return $this->getCustomer(new GetCustomerRequest($this->id));
    }

    /**
     * @inheritDoc
     */
    public function deleteCustomer(string $id): void
    {
        Sep12ProvidedField::where('sep12_customer_id', $id)->delete();
        Sep12Customer::destroy($id);
    }

}
