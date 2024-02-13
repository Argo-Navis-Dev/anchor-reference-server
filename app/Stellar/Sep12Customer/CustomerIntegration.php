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
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerCallbackRequest;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerRequest;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerResponse;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerVerificationRequest;
use ArgoNavis\PhpAnchorSdk\exception\CustomerNotFoundForId;
use ArgoNavis\PhpAnchorSdk\exception\SepNotAuthorized;

class CustomerIntegration implements ICustomerIntegration
{
    /**
     * @inheritDoc
     */
    public function getCustomer(GetCustomerRequest $request): GetCustomerResponse
    {
        $account = $request->account;
        $memo = $request->memo;
        $customer = null;

        if ($request->id != null) {
            $customer = Sep12Customer::where('id', $request->id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId($request->id);
            }

            if ($account !== $customer->account_id || $memo !== $customer->memo) {
                throw new SepNotAuthorized('Unauthorized');
            }

        } else if ($request->account != null) {
            $customer = Sep12Helper::getSep12CustomerByAccountId($account, $memo, $request->type);
        }

        return Sep12Helper::buildCustomerResponse($customer);
    }

    /**
     * @inheritDoc
     */
    public function putCustomer(PutCustomerRequest $request): PutCustomerResponse
    {
        $account = $request->account;
        $memo = $request->memo;

        $customer = null;

        if ($request->id != null) {
            $customer = Sep12Customer::where('id', $request->id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId($request->id);
            }

            if ($account !== $customer->account_id || $memo !== $customer->memo) {
                throw new SepNotAuthorized('Unauthorized');
            }

        } else if ($request->account != null) {
            $customer = Sep12Helper::getSep12CustomerByAccountId($account, $memo, $request->type);
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
        $account = $request->account;
        $memo = $request->memo;

        $customer = Sep12Customer::where('id', $request->id)->first();
        if ($customer === null) {
            throw new CustomerNotFoundForId($request->id);
        }

        if ($account !== $customer->account_id || $memo !== $customer->memo) {
            throw new SepNotAuthorized('Unauthorized');
        }

        Sep12Helper::handleVerification($customer, $request->verificationFields);

        return $this->getCustomer(new GetCustomerRequest($account, $memo, $request->id));
    }

    /**
     * @inheritDoc
     */
    public function deleteCustomer(string $id): void
    {
        Sep12ProvidedField::where('sep12_customer_id', $id)->delete();
        Sep12Customer::destroy($id);
    }

    /**
     * @inheritDoc
     */
    public function putCustomerCallback(PutCustomerCallbackRequest $request): void
    {
        $account = $request->account;
        $memo = $request->memo;

        $customer = null;

        if ($request->id != null) {
            $customer = Sep12Customer::where('id', $request->id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId($request->id);
            }

            if ($account !== $customer->account_id || $memo !== $customer->memo) {
                throw new SepNotAuthorized('Unauthorized');
            }
        } else {

            $customer = Sep12Helper::getSep12CustomerByAccountId($account, $memo);
            if ($customer === null) {
                $id = $account;
                if ($memo !== null) {
                    $id .= ':'.$memo;
                }
                throw new CustomerNotFoundForId($id);
            }
        }

        $customer->callback_url = $request->url;
        $customer->save();
    }
}
