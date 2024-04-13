@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-uppercase mb-0 text-primary">Manage Users</h5>
                </div>
                <div class="table-responsive">              
                    <table
                        id = "admin-users-table"                        
                        data-minimum-count-columns="1"                                                
                        data-id-field="id"                                                
                        data-ajax="loadAdminUsersData">
                    </table>

                </div>
            </div>
        </div>
    </div>
</div>
</script>
@endsection

