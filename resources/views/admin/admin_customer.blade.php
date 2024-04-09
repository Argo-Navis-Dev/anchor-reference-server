@extends('layouts.app')

@section('content')
<div class="container">
    @if(!empty($error))
    <div class="alert alert-danger">
        {{ $error }}
    </div>
    @else

    <div class="row justify-content-center">
        <div class="col-md-8">
            @if(!empty($success))
            <div class="alert alert-success">
                {{ $success }}
            </div>
            @endif
            <div class="card">
                <div class="card-header text-primary">{{ __('UPDATE CUSTOMER') }}</div>

                <form method="POST" action="{{ route('update_admin_customer', $customer['id']) }}">
                    @csrf
                    <div class="card-body">
                        <!-- The customer status -->
                        <div class="col-md-12">
                            <label for="status"
                                class="col-md-3 col-form-label text-md-start">{{ __('Customer status') }}</label>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <select id="status" class="form-control form-select @error('status') is-invalid @enderror"
                                            name="status" required>
                                            <option value="ACCEPTED"
                                                {{ $customer['status'] == "ACCEPTED" ? "selected" : "" }}>
                                                ACCEPTED</option>
                                            <option value="PROCESSING"
                                                {{ $customer['status'] == "PROCESSING" ? "selected" : "" }}>
                                                PROCESSING</option>
                                            <option value="NEEDS_INFO"
                                                {{ $customer['status'] == "NEEDS_INFO" ? "selected" : "" }}>
                                                NEEDS_INFO</option>
                                            <option value="REJECTED"
                                                {{ $customer['status'] == "REJECTED" ? "selected" : "" }}>
                                                REJECTED</option>
                                        </select>
                                        @error('status')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                        @enderror
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- The customer fields -->

                        @include('admin/customer_fields/shorttext_field',
                        ['customerID' => $customer['id'], 'fieldName' => 'first_name', 'fieldValue' =>
                        $customer['first_name'],
                        'fieldID' => $customer['first_name_id'], 'statusValue' => $customer['first_name_status'],
                        'localizedLabel' => __('First Name')])

                        @include('admin/customer_fields/shorttext_field',
                        ['customerID' => $customer['id'], 'fieldName' => 'last_name',
                        'fieldValue' => $customer['last_name'], 'fieldID' => $customer['last_name_id'],
                        'statusValue' => $customer['last_name_status'], 'localizedLabel' => __('Last Name')])

                        @include('admin/customer_fields/shorttext_field',
                        ['customerID' => $customer['id'], 'fieldName' => 'email_address',
                        'fieldValue' => $customer['email_address'], 'fieldID' => $customer['email_address_id'],
                        'statusValue' => $customer['email_address_status'], 'localizedLabel' => __('Email address')])

                        @include('admin/customer_fields/shorttext_field',
                        ['customerID' => $customer['id'], 'fieldName' => 'id_number',
                        'fieldValue' => $customer['id_number'], 'fieldID' => $customer['id_number_id'],
                        'statusValue' => $customer['id_number_status'], 'localizedLabel' => __('ID number')])

                        @include('admin/customer_fields/dropdown_field',
                        ['customerID' => $customer['id'], 'fieldName' => 'id_type',
                        'fieldValue' => $customer['id_type'], 'fieldID' => $customer['id_type_id'],
                        'statusValue' => $customer['id_type_status'], 'localizedLabel' => __('ID type'),
                        'options' => [['label' => 'ID card', 'id' => 'ID_CARD'], ['label' => 'Passport', 'id' =>
                        'Passport']]])

                        @include('admin/customer_fields/binary_field',
                        ['customerID' => $customer['id'], 'fieldName' => 'photo_id_front', 'fieldID' =>
                        $customer['photo_id_front_id'], 'statusValue' => $customer['photo_id_front_status'],
                        'localizedLabel' => __('Photo ID front')])

                        @include('admin/customer_fields/binary_field',
                        ['customerID' => $customer['id'], 'fieldName' => 'photo_id_back', 'fieldID' =>
                        $customer['photo_id_back_id'], 'statusValue' => $customer['photo_id_back_status'],
                        'localizedLabel' => __('Photo ID back')])
                        <div class="dropdown">
                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4-start">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Save') }}
                                </button>

                                <button class="btn btn-secondary ms-2" onclick="event.preventDefault(); window.location.href = '{{ route('admin_customers')}}'">
                                    {{ __('Done') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif


</div>
</script>
@endsection