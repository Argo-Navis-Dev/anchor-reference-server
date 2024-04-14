@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-uppercase mb-0 text-primary">Manage Users</h5>
                </div>
                <div class="card users-table-wrapper">
                    <div id="users-table-toolbar" class="users-table-toolbar">    
                        <div class="ms-3 mb-3">
                                <button id="add-user" type="button" class="btn btn-primary" onclick="window.location.href='/user/edit'">Create new user</button>                    
                        </div>                
                    </div>

                    <div class="table-responsive">              
                        <table
                            id = "admin-users-table"                        
                            data-toolbar="#users-table-toolbar"        
                            data-minimum-count-columns="1"                                                
                            data-id-field="id"        
                            data-search="true"                                            
                            data-ajax="loadUsers">
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</script>
@endsection

