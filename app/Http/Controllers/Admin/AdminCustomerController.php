<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be.
// found in the LICENSE file.

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Stellar\Sep12Customer\CustomerIntegration;
use ArgoNavis\PhpAnchorSdk\Sep12\Sep12Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Sep12Customer;
use App\Models\Sep12ProvidedField;
use App\Models\Sep12Field;
use Illuminate\Validation\Validator;
use ArgoNavis\PhpAnchorSdk\callback\GetCustomerRequest;
use GuzzleHttp\Client;

/**
 * Controller for the admin customers and customer page.
 */
class AdminCustomerController extends Controller    
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
     * Loads the customers page.
     *
     * @return \Illuminate\Contracts\Support\Renderable The view to be rendered: admin_customers.blade.php
     */
    public function loadAdminCustomers()
    {
        Log::debug('Accessing the admin customers page.');
        
        $customers = Sep12Customer::all();
        $customersData = array();

        $fields = Sep12Field::all();
        $fieldIDToBean = $fields->keyBy('id')->all();     
        
        foreach ($customers as $customer) {            
            $customerData = array();
            Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get()->each(function($providedField) use (&$customerData, $fieldIDToBean) {                                
                $fieldName = $fieldIDToBean[$providedField->sep12_field_id]->key;                
                $customerData[$fieldName] = $providedField->string_value;                
            });
            $customerData['account_id'] = $customer->account_id;
            $customerData['id'] = $customer->id;
            $customerData['status'] = $customer->status;
            $customerData['type'] = $customer->type;
            $customersData[] = $customerData;
        }        
        LOG::debug('The customer data is: ' . json_encode($customersData));
        return view('/admin/admin_customers', ['customers' => $customersData]);
    }

    /**
     * Deletes the passed customer.
     *
     * @param  Request  $request the request object.
     * @return \Illuminate\Http\Response The response object contianing a JSON body.
     */
    public function deleteAdminCustomer(Request $request) 
    {
        $id = $request->input('id');
        Log::debug('Deleting the customer by id: ' . $id);  
        $customer = Sep12Customer::find($id);
        if ($customer) {
            // Delete all fields associated with the customer
            Sep12ProvidedField::where('sep12_customer_id', $customer->id)->delete();
            $customer->delete(); // Delete the customer
            Log::debug('Customer deleted successfully!');
            return response()->json(['success' => true, 'message' => 'The custoemr has been deleted successfully'], 200); 
        } else {
            Log::debug('Customer not found!');
            return response()->json(['success' => 'false','error' => 'Customer not found!'], 404);
        }        
    }
    
    /**
     * Loads the customer page which permits editing the customer data.
     *
     * @param  int  $id the customer id.
     * @return \Illuminate\Contracts\Support\Renderable The view to be rendered: admin_customers.blade.php
     */
    public function loadAdminCustomer($id) 
    {
        LOG::debug('Accessing customer page: ' . $id);
        $customerData = $this->getCustomerData($id);
        if (!$customerData) {
            Log::debug('Customer not found!');
            return view('/admin/admin_customer', ['error' => "Not found!"]);     
        }        
        return view('/admin/admin_customer', ['customer' => $customerData]); // Pass the user to the view
    }


    /**
     * Creates the customer data for the passed ID.
     *
     * @param int $id The ID of the customer to be loaded.
     * @return array The customer data model.
     */
    private function getCustomerData($id)
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
        Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get()->each(function($providedField) use (&$customerData, $fieldIDToBean) {                                
            $fieldName = $fieldIDToBean[$providedField->sep12_field_id]->key;                
            $customerData[$fieldName] = $providedField->string_value;
            $customerData[$fieldName . '_status'] = $providedField->status;
            $customerData[$fieldName . '_id'] = $providedField->id;
        });
        //The default status for the missing fields
        $DEFAULT_STATUS_FOR_MISSING_FIELD = "PROCESSING";
        //Populate the fields that are not in the provided fields (in order to display them in the view).
        foreach ($fieldIDToBean as $fieldID => $fieldBean) {
            if (!array_key_exists($fieldBean->key, $customerData)) {
                $customerData[$fieldBean->key] = '';
                $customerData[$fieldBean->key . '_status'] = $DEFAULT_STATUS_FOR_MISSING_FIELD;
                $customerData[$fieldBean->key . '_id'] = null;
            }
        }
        $customerData['account_id'] = $customer->account_id;
        $customerData['id'] = $customer->id;
        $customerData['status'] = $customer->status;
        return $customerData;
    }


    /**
     * Updates a customer (saves the submitted data in DB).
     *
     * @param  \Illuminate\Http\Request $request The request object.
     * @param  int  $id The customer ID.
     * @return \Illuminate\Contracts\Support\Renderable The view to be rendered: admin_customer.blade.php
     */
    public function updateAdminCustomer(Request $request, $id)
    {
        LOG::debug('Updating the customer data by customer ID: ' . $id);        
        $customer = Sep12Customer::find($id);
        if (!$customer) {
            Log::debug('Customer not found!');
            return view('/admin/admin_customer', ['error' => "Customer not found!"]);     
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
            'status' => 'required|string|in:ACCEPTED,PROCESSING,NEEDS_INFO,REJECTED',
        ];        
        $FIELD_TO_IGNORE_ON_VALIDATION = ['photo_id_front', 'photo_id_back'];
        LOG::debug('Exists: ' . array_key_exists("photo_id_front", $FIELD_TO_IGNORE_ON_VALIDATION). ' ' . json_encode($FIELD_TO_IGNORE_ON_VALIDATION));
        foreach ($fields as $field) {
            if(!in_array($field->key, $FIELD_TO_IGNORE_ON_VALIDATION)) {
                $fieldsToValidate[$field->key] = 'required|string';
            }            
            $fieldsToValidate[$field->key . '_status'] = 'required|string|in:ACCEPTED,PROCESSING,REJECTED,VERIFICATION_REQUIRED';            
        }        
        $request->validate($fieldsToValidate);  
        LOG::debug('The submitted data has been validated successfully!');
        
        //Save the customer status
        $customerStatus = $submittedData['status'];                
        if($customerStatus) {
            if($customerStatus != $customer->status) {
                LOG::debug('The customer status has been changed from: ' . $customer->status . ' to: ' . $customerStatus);
            }        
            $customer->status = $customerStatus;
            $customer->save();
        }

        foreach ($submittedData as $fieldName => $fieldValue) {   
            //Ignore all fields that are not in the database sep_12_fields table           
            if(array_key_exists($fieldName, $fieldNameToBean) == false) {                
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
        return view('/admin/admin_customer', ['success' => 'The customer data has been updated successfully!', 'customer' => $customerData]);
    }
    
    
    /**
     * Updates the customer provided field.
     *
     * @param integer $fieldID The ID of the field.
     * @param object:Sep12ProvidedField $providedFields The list of provided fields.
     * @param object $field Sep12Field The field object.
     * @param mixed $fieldValue The current value of the field.
     * @param mixed $newStatusValue The new status value of the field.
     * @param Sep12Customer $customer The customer to be updated.
     * @return void
     */
    private function updateCustomerField($field, $providedFields, $fieldName, $fieldValue, $newStatusValue, $customer) 
    {                                   
        $fieldID = $field->id;
        LOG::debug('Updating the field: ' . $fieldName . ' sep12_field_id: ' . $fieldID . ' with value: ' . $fieldValue . ' and status: ' . $newStatusValue);            
        $providedField = $providedFields->where('sep12_field_id',  $fieldID)->first();
        if($providedField) {
            $currentStatuss = $providedField->status;            
            $providedField->status = $newStatusValue;                    
            if($fieldValue && $field->type == 'string') {
                $providedField->string_value = $fieldValue;
            }
            $providedField->save();
            if($currentStatuss != $newStatusValue) {
                LOG::debug('The field ' . $fieldName . ' status has been changed from: ' . $currentStatuss . ' to: ' . $newStatusValue);
                $this->onCustomerFieldStatusChnaged($customer);            
            }
        }
        else {
            $providedField = new Sep12ProvidedField();
            $providedField->sep12_customer_id = $customer->id;
            $providedField->sep12_field_id = $fieldID;
            if($fieldValue && $field->type == 'string') {
                $providedField->string_value = $fieldValue;
            }
            $providedField->status = 'PROCESSING';
            $providedField->save();
        }        
    }

    /**
     * Handler for customer field status change.
     * Calls the SEP12 service to get the customer data and sends it to the callback URL.
     *
     * @param Sep12Customer $customer The customer object.
     * @return void
     */    
    private function onCustomerFieldStatusChnaged($customer) 
    {
        $callbackUrl = $customer->callback_url;
        LOG::debug('The customer field status change callback URL: ' . $callbackUrl);
        if ($callbackUrl && !empty($callbackUrl)) {
        
            $getCustomerRequest = new GetCustomerRequest($customer->account_id, $customer->memo);
            $customerIntegration = new CustomerIntegration();
            $sep12CustomerData = $customerIntegration->getCustomer($getCustomerRequest);
                        
            $httpClient = new Client();
            $response = $httpClient->post($customer->callback_url, [
                'json' => $sep12CustomerData
            ]);
            // Check the response status code
            if ($response->getStatusCode() == 200) {
                LOG::debug('The customer status change callback has been sent successfully!');
            } else {
                LOG::error('The customer status change callback has failed!');
            }
        }
    }

    /**
     * Retrieves the passed custome image field or a dummy image if it does not exist.
     *
     * @param int $id The ID of the customer.
     * @param int $fieldID The ID of the image field.
     * @return void
     */
    public function getCustomerImgField($id, $fieldID)
    {
        LOG::debug('Loading image field: ' . $fieldID . ' by customer: ' . $id);        
        $imgField = Sep12ProvidedField::where('sep12_customer_id', $id)
            ->where('id', $fieldID)
            ->first();
        if ($imgField && $imgField->binary_value) {            
            $size = strlen($imgField->binary_value);
            if($size == 0) {
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