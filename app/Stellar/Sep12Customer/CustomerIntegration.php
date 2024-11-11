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
use Illuminate\Support\Facades\Log;

use function json_encode;

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
        $id = $request->id;
        Log::debug(
            'Retrieving customer.',
            ['context' => 'sep12', 'operation' => 'get_customer',
                'account' => $account, 'memo' => $memo, 'id' => $id,
            ],
        );
        if ($id != null) {
            $customer = Sep12Customer::where('id', $request->id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId(id: $id);
            }

            if ($account !== $customer->account_id || $memo !== $customer->memo) {
                throw new SepNotAuthorized('Unauthorized');
            }
        } elseif ($request->account != null) {
            $customer = Sep12Helper::getSep12CustomerByAccountId($account, $memo, $request->type);
        }
        Log::debug(
            'Customer found.',
            ['context' => 'sep12', 'operation' => 'get_customer', 'customer_db_record' => json_encode($customer)],
        );

        return Sep12Helper::buildCustomerResponse($customer, $request->lang);
    }

    /**
     * @inheritDoc
     */
    public function putCustomer(PutCustomerRequest $request): PutCustomerResponse
    {
        $account = $request->account;
        $memo = $request->memo;

        $customer = null;

        Log::debug(
            'Updating customer information.',
            ['context' => 'sep12', 'operation' => 'put_customer',
                'account' => $account, 'memo' => $memo, 'id' => $request->id, 'request' => json_encode($request),
            ],
        );
        $id = $request->id;
        if ($id != null) {
            $customer = Sep12Customer::where('id', $id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId(id: $id);
            }

            if ($account !== $customer->account_id || $memo !== $customer->memo) {
                throw new SepNotAuthorized('Unauthorized');
            }
        } elseif ($request->account != null) {
            $customer = Sep12Helper::getSep12CustomerByAccountId($account, $memo, $request->type);
        }

        if ($customer === null) {
            Log::debug(
                'Creating new customer record.',
                ['context' => 'sep12', 'operation' => 'put_customer'],
            );

            // new customer
            $customer = Sep12Helper::newSep12Customer($request);
        } else {
            Log::debug(
                'Customer found, updating it\'s data.',
                ['context' => 'sep12', 'operation' => 'put_customer'],
            );

            // update customer
            $customer = Sep12Helper::updateSep12Customer($customer, $request);
        }
        Log::debug(
            'Customer info has been saved successfully.',
            ['context' => 'sep12', 'operation' => 'put_customer', 'customer_db_record' => json_encode($customer)],
        );

        return new PutCustomerResponse(id: $customer->id);
    }

    /**
     * @inheritDoc
     */
    public function putCustomerVerification(PutCustomerVerificationRequest $request): GetCustomerResponse
    {
        $account = $request->account;
        $memo = $request->memo;
        $id = $request->id;

        Log::debug(
            'Executing customer verification.',
            ['context' => 'sep12', 'operation' => 'put_customer_verification',
                'account' => $account, 'memo' => $memo, 'id' => $id,
            ],
        );

        $customer = Sep12Customer::where('id', $id)->first();
        if ($customer === null) {
            throw new CustomerNotFoundForId(id: $id);
        }

        if ($account !== $customer->account_id || $memo !== $customer->memo) {
            throw new SepNotAuthorized('Unauthorized');
        }

        Sep12Helper::handleVerification($customer, $request->verificationFields);
        Log::debug(
            'Customer verification has been executed successfully.',
            ['context' => 'sep12', 'operation' => 'put_customer_verification',
                'customer_db_record' => json_encode($customer),
            ],
        );

        return $this->getCustomer(new GetCustomerRequest($account, $memo, $request->id));
    }

    /**
     * @inheritDoc
     */
    public function deleteCustomer(string $id): void
    {
        Log::debug(
            'Deleting customer.',
            ['context' => 'sep12', 'operation' => 'delete_customer', 'id' => $id],
        );

        Sep12ProvidedField::where('sep12_customer_id', $id)->delete();
        Sep12Customer::destroy($id);
        Log::debug(
            'Customer has been deleted successfully.',
            ['context' => 'sep12', 'operation' => 'delete_customer'],
        );
    }

    /**
     * @inheritDoc
     */
    public function putCustomerCallback(PutCustomerCallbackRequest $request): void
    {
        $account = $request->account;
        $memo = $request->memo;
        $id = $request->id;
        Log::debug(
            'Saving customer callback.',
            ['context' => 'sep12', 'operation' => 'put_customer_callback',
                'account' => $account, 'memo' => $memo, 'id' => $id, 'callback_url' => $request->url
            ],
        );

        $customer = null;

        if ($request->id != null) {
            $customer = Sep12Customer::where('id', $request->id)->first();
            if ($customer === null) {
                throw new CustomerNotFoundForId(id: $request->id);
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

                throw new CustomerNotFoundForId(id: $id);
            }
        }

        $customer->callback_url = $request->url;
        $customer->save();

        Log::debug(
            'Customer callback has been saved successfully.',
            ['context' => 'sep12', 'operation' => 'put_customer_callback',
                'customer_db_record' => json_encode($customer)],
        );
    }
}
