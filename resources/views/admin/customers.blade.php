@extends('layouts.app')
@section('pageType', 'customers-page')
@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card customers-table-wrapper">
                <div class="card-body">
                    <h5 class="card-title text-uppercase mb-0 text-primary">Manage customers</h5>
                </div>                    
                <div id="customers-table-toolbar" class="customers-table-toolbar">    
                    <div class="ms-3 mb-3">
                        <label for="customer-status-filter" class="col-form-label text-md-start">{{ __('Filter by customer status') }}</label>
                        <select id="customer-status-filter" class="form-control form-select">                                                                                    
                            <option value="NONE" selected>NONE</option>
                            <option value="ACCEPTED" >ACCEPTED</option>
                            <option value="PROCESSING">PROCESSING</option>
                            <option value="NEEDS_INFO">NEEDS_INFO</option>
                            <option value="REJECTED">REJECTED</option>                    
                        </select>                
                    </div>                
                </div>

                <table
                    id = "customers-table"
                    data-toolbar="#customers-table-toolbar"                    
                    data-minimum-count-columns="1"                                                
                    data-id-field="id"  
                    data-search="true"                                              
                    data-ajax="loadCustomers">
                </table>

            </div>
        </div>
    </div>
</div>
</script>
@endsection

