@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-uppercase mb-0 text-primary">Manage customers</h5>
                </div>
                <div class="table-responsive">
                    <table class="table no-wrap user-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="border-0 text-uppercase font-medium pl-4">#</th>                                
                                <th scope="col" class="border-0 text-uppercase font-medium">First Name</th>
                                <th scope="col" class="border-0 text-uppercase font-medium">Last name</th>                                
                                <th scope="col" class="border-0 text-uppercase font-medium">Account ID</th>
                                <th scope="col" class="border-0 text-uppercase font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($customers as $index => $customer)
                            <td class="pl-4 align-middle">{{ $index + 1 }}</td>                            
                            <td class="align-middle">
                                @if(isset($customer['first_name']))
                                    <h5 class="font-medium mb-0">{{ $customer['first_name'] }}</h5>   
                                @else
                                    <h5 class="font-medium mb-0">N/A</h5>
                                @endif  
                            </td>

                            <td class="align-middle">
                                @if(isset($customer['last_name']))
                                    <h5 class="font-medium mb-0">{{ $customer['last_name'] }}</h5>
                                @else
                                    <h5 class="font-medium mb-0">N/A</h5>
                                @endif
                            </td>  
                            <td class="align-middle">
                                <h5 class="font-medium mb-0">{{ $customer['account_id'] }}</h5>
                                <span class="text-muted"><strong>Status:</strong> {{ $customer['status'] }}</span>
                                <span class="text-muted"><strong>Type:</strong> {{ $customer['type'] }}</span>
                            </td>
                            <td class="align-middle">                                                             
                                <button type="button"
                                    onclick="window.location='{{ URL::route('admin_customer', $customer['id']) }}'"                                    
                                    class="edit-user btn btn-outline-info btn-circle btn-lg btn-circle ml-2"><i
                                        class="fa fa-edit"></i> </button>
                                <button type="button"                                    
                                    data-customer-id="{{ $customer['id'] }}"

                                    @if(isset($customer['first_name']) && isset($customer['last_name']))
                                        data-customer-name="{{ $customer['first_name'] }} {{ $customer['last_name'] }}"
                                    @endif
                                    class="delete-customer btn btn-outline-info btn-circle btn-lg btn-circle ml-2"><i
                                        class="fa fa-trash"></i> </button>        
                            </td>                      
                        @endforeach

                
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</script>
@endsection

