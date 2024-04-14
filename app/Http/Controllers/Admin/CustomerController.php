<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Stellar\Sep12Customer\Sep12Helper;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Sep12Customer;
use App\Models\Sep12ProvidedField;
use App\Models\Sep12Field;


/**
 * Controller for administering customers.
 */
class CustomerController extends Controller
{

    /**
     * Create a new controller instance.
     * The auth middleware is used to authenticate the user.
     * This controller can be accessed exclusively by authenticated users.
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Renders the customers page.
     *
     * @return \Illuminate\Contracts\Support\Renderable The view to be rendered: customers.blade.php
     */
    public function index()
    {
        Log::debug('Accessing the customers page.');
        return view('/admin/customers');
    }

    /**
     * Renders the customer page which permits the user to edit the customer data.
     *
     * @param string $id the customer id.
     * @return \Illuminate\Contracts\Support\Renderable The view to be rendered: customers.blade.php
     */
    public function show(string $id)
    {
        LOG::debug('Rendering the customer page: ' . $id);
        $customerData = $this->getCustomerData($id);
        if (!$customerData) {
            Log::debug('Customer not found!');
            return view('/admin/customer', ['error' => "Customer not found!"]);
        }
        return view('/admin/customer', ['customer' => $customerData, 'fields' => $this->getFieldsData()]); // Pass the user to the view
    }

    /**
     * Loads all customers.
     *
     * @return \Illuminate\Http\Response The response object contianing the customers data encoded to JSON.
     */
    public function loadCustomers()
    {
        Log::debug('Loading all customers.');
        $customersData = $this->getCustomersData();
        return response()->json($customersData, 200);
    }

    /**
     * 
     * Retrieves all customers data from the database and formats it in order to be displayed in the customers page.
     * @return array The customers data.
     */
    private function getCustomersData()
    {
        $customers = Sep12Customer::all();
        $customersData = array();

        $fields = Sep12Field::all();
        $fieldIDToBean = $fields->keyBy('id')->all();

        foreach ($customers as $customer) {
            $customerData = array();
            Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get()->each(function ($providedField) use (&$customerData, $fieldIDToBean) {
                $fieldName = $fieldIDToBean[$providedField->sep12_field_id]->key;
                $customerData[$fieldName] = $providedField->string_value;
            });
            $customerData['account_id'] = $customer->account_id;
            $customerData['id'] = $customer->id;
            $customerData['status'] = $customer->status;
            $customerData['type'] = $customer->type;
            $customersData[] = $customerData;
        }
        return $customersData;
    }

    /**
     * Deletes the passed customer.
     *
     * @param  Request  $request the request object.
     * @return \Illuminate\Http\Response The response object contianing a JSON body.
     */
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        Log::debug('Deleting the customer by id: ' . $id);
        $customer = Sep12Customer::find($id);
        if ($customer) {
            // Delete all fields associated with the customer
            Sep12ProvidedField::where('sep12_customer_id', $customer->id)->delete();
            $customer->delete(); // Delete the customer
            Log::debug('Customer deleted successfully!');
            return response()->json(['success' => true, 'message' => 'The customer has been deleted successfully'], 200);
        } else {
            Log::debug('Customer not found!');
            return response()->json(['success' => 'false', 'error' => 'Customer not found!'], 404);
        }
    }


    /**
     * Retrieves all field definition data from the database and formats it, in order to be displayed in the customer page.
     *
     * @return array The fields data.
     */
    private function getFieldsData()
    {
        $sep12Fields = Sep12Field::all();
        $fieldsJson = [];
        foreach ($sep12Fields as $field) {
            $fieldJson = [];
            $fieldJson['id'] = $field->id;
            $fieldJson['key'] = $field->key;
            $fieldJson['type'] = $field->type;
            if ($field->choices !== null) {
                $choices = explode(',', $field->choices);
                $labelIdChoices = [];
                foreach ($choices as $choice) {
                    $ch = [];
                    $ch['id'] = $choice;
                    $ch['label'] = $choice;
                    $labelIdChoices[] = $ch;
                }
                $fieldJson['choices'] = $labelIdChoices;
            }
            $fieldsJson[] = $fieldJson;
        }
        return $fieldsJson;

    }

    /**
     * Retrives the customer data for the passed customer ID.
     *
     * @param string $id The ID of the customer to be loaded.
     * @return array The customer data model.
     */
    private function getCustomerData(string $id)
    {
        $customer = Sep12Customer::find($id); // Load the user by id

        if (!$customer) {
            Log::debug('Customer not found: ' . $id);
            return [];
        }
        $fields = Sep12Field::all();
        $fieldIDToBean = $fields->keyBy('id')->all();

        $customerData = array();
        //Populate the fields from the provided fields
        Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get()->each(function ($providedField) use (&$customerData, $fieldIDToBean) {
            $fieldName = $fieldIDToBean[$providedField->sep12_field_id]->key;
            $customerData[$fieldName] = $providedField->string_value;
            $customerData[$fieldName . '_status'] = $providedField->status;
            $customerData[$fieldName . '_id'] = $providedField->id;
        });
        $customerData['account_id'] = $customer->account_id;
        $customerData['id'] = $customer->id;
        $customerData['status'] = $customer->status;
        return $customerData;
    }


    /**
     * Updates a customer (saves the submitted data in DB).
     *
     * @param  \Illuminate\Http\Request $request The request object.
     * @param  string $id The customer ID.
     * @return \Illuminate\Contracts\Support\Renderable The view to be rendered: customer.blade.php
     */
    public function store(Request $request, string $id)
    {
        LOG::debug('Updating the customer data by customer ID: ' . $id);
        $customer = Sep12Customer::find($id);
        $oldCustomerStatus = $customer->status;

        if (!$customer) {
            Log::debug('Customer not found!');
            return view('/admin/customer', ['error' => "Customer not found!"]);
        }
        //Load the fields
        $fields = Sep12Field::all();
        //Create a map: fieldName -> fieldBean
        $fieldNameToBean = $fields->keyBy('key')->all();

        $submittedData = $request->all();
        LOG::debug('The submitted data: ' . json_encode($submittedData));

        $providedFields = Sep12ProvidedField::where('sep12_customer_id', $id)->get();
        //Validate the submitted data
        $fieldsToValidate = [
            'status' => 'string|in:ACCEPTED,PROCESSING,NEEDS_INFO,REJECTED',
        ];
        $FIELD_TO_IGNORE_ON_VALIDATION = ['photo_id_front', 'photo_id_back'];
        foreach ($fields as $field) {
            if (!in_array($field->key, $FIELD_TO_IGNORE_ON_VALIDATION)) {
                $fieldsToValidate[$field->key] = 'required|string';
            }
            $fieldsToValidate[$field->key . '_status'] = 'required|string|in:ACCEPTED,PROCESSING,REJECTED,VERIFICATION_REQUIRED';
        }
        $request->validate($fieldsToValidate);
        LOG::debug('The submitted data has been validated successfully!');

        //Save the customer status
        $customerStatus = $submittedData['status'];
        if ($customerStatus) {
            $customer->status = $customerStatus;
            $customer->save();
            $customer->refresh();
        }

        foreach ($submittedData as $fieldName => $fieldValue) {
            //Ignore all fields that are not in the database sep_12_fields table           
            if (array_key_exists($fieldName, $fieldNameToBean) == false) {
                continue;
            }
            $newStatusValue = $submittedData[$fieldName . '_status'];
            $field = $fieldNameToBean[$fieldName];
            $this->updateCustomerField($field, $providedFields, $fieldName, $fieldValue, $newStatusValue, $customer);
        }
        //The images of the ID cards are not submitted, so save only it's status.
        $this->updateCustomerField($fieldNameToBean['photo_id_front'], $providedFields, 'photo_id_front', null, $submittedData['photo_id_front_status'], $customer);
        $this->updateCustomerField($fieldNameToBean['photo_id_back'], $providedFields, 'photo_id_back', null, $submittedData['photo_id_back_status'], $customer);
        $customerData = $this->getCustomerData($id);
        if ($oldCustomerStatus != $customer->status) {
            LOG::debug('The customer status has been changed from: ' . $oldCustomerStatus . ' to: ' . $customerStatus);
            Sep12Helper::onCustomerStatusChanged($customer);
        }
        return view('/admin/customer', ['success' => 'The customer data has been updated successfully!', 'customer' => $customerData, 'fields' => $this->getFieldsData()]);
    }


    /**
     * Updates or creates the passed customer provided field.
     *
     * @param Sep12Field $field The field definition bean.
     * @param object $providedFields The provided fields.
     * @param string $fieldName The name of the field.
     * @param string $fieldValue The value of the field.
     * @param string $newStatusValue The new status of the field.
     * @param Sep12Customer $customer The customer bean.
     * @return void
     */
    private function updateCustomerField(Sep12Field $field, object $providedFields, $fieldName, ?string $fieldValue = null, string $newStatusValue, Sep12Customer $customer)
    {
        $fieldID = $field->id;
        LOG::debug('Updating the field: ' . $fieldName . ' sep12_field_id: ' . $fieldID . ' with value: ' . $fieldValue . ' and status: ' . $newStatusValue);
        $providedField = $providedFields->where('sep12_field_id', $fieldID)->first();
        if ($providedField) {
            $currentStatuss = $providedField->status;
            $providedField->status = $newStatusValue;
            if ($fieldValue && $field->type == 'string') {
                $providedField->string_value = $fieldValue;
            }
            $providedField->save();
        } else {
            $providedField = new Sep12ProvidedField();
            $providedField->sep12_customer_id = $customer->id;
            $providedField->sep12_field_id = $fieldID;
            if ($fieldValue && $field->type == 'string') {
                $providedField->string_value = $fieldValue;
            }
            $providedField->status = ProvidedCustomerFieldStatus::ACCEPTED;
            $providedField->save();
        }
    }

    /**
     * Retrieves the passed customer binary (image) field or a dummy image if it does not exist.
     *
     * @param string $id The ID of the customer.
     * @param int $providedFieldID The ID of the image field.
     * @return void
     */
    public function getBinaryField(string $id, int $providedFieldID)
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