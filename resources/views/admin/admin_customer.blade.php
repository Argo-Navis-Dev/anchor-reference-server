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
                                        <select id="customer-status" class="form-control form-select @error('status') is-invalid @enderror"
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

                        @if(isset($fields))                       
                            @foreach($fields as $field)
                                @switch($field['type'])
                                    @case('string')
                                        @if(isset($field['choices']))
                                            @include('admin/customer_fields/dropdown_field', [
                                                'customerID' => $customer['id'], 
                                                'fieldName' => $field['key'],
                                                'fieldValue' => $customer[$field['key']] ?? '',                                                 
                                                'statusValue' => $customer[$field['key'].'_status'] ?? 'PROCESSING', 
                                                'localizedLabel' => __('admin_dashboard.' . $field['key']),
                                                'options' => $field['choices']])
                                        @else
                                            @include('admin/customer_fields/string_field', [
                                                'customerID' => $customer['id'],
                                                'fieldName' => $field['key'],
                                                'fieldValue' => $customer[$field['key']] ?? '',
                                                'statusValue' => $customer[$field['key'].'_status'] ?? 'PROCESSING',
                                                'localizedLabel' => __('admin_dashboard.' . $field['key'])
                                            ])
                                        @endif
                                    @break
                                    @case ('binary')
                                        @include('admin/customer_fields/binary_field', [
                                            'customerID' => $customer['id'],
                                            'fieldName' => $field['key'],
                                            'providedFieldID' => $customer[$field['key'] . '_id'] ?? 'null',
                                            'statusValue' => $customer[$field['key'].'_status'] ?? 'PROCESSING',
                                            'localizedLabel' => __('admin_dashboard.' . $field['key'])
                                        ])
                                @endswitch                                                            
                            @endforeach                     
                        @endif
         
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