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
                    <table class="table no-wrap user-table mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="border-0 text-uppercase font-medium pl-4">#</th>
                                <th scope="col" class="border-0 text-uppercase font-medium">Name</th>
                                <th scope="col" class="border-0 text-uppercase font-medium">Email</th>
                                <th scope="col" class="border-0 text-uppercase font-medium">Created at</th>
                                <th scope="col" class="border-0 text-uppercase font-medium">Updated at</th>
                                <th scope="col" class="border-0 text-uppercase font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $index => $user)
                            <tr>
                                <td class="pl-4 align-middle">{{ $index + 1 }}</td>
                                <td class="align-middle">
                                    <h5 class="font-medium mb-0">{{ $user->name }}</h5>   
                                </td>
                                <td class="align-middle">
                                    <h5 class="font-medium mb-0">{{ $user->email }}</h5>
                                </td>
                                <td class="align-middle">
                                    <h5 class="font-medium mb-0">{{ $user->created_at }}</h5>
                                </td>
                                <td class="align-middle">
                                    <h5 class="font-medium mb-0">{{ $user->updated_at }}</h5>
                                </td>
                             
                                <td>                                                                        
                                    <button type="button"
                                        onclick="window.location='{{ URL::route('admin_user', $user->id) }}'"                                        
                                        class="edit-user btn btn-outline-info btn-circle btn-lg btn-circle me-2"><i
                                            class="fa fa-edit"></i> </button>               

                                        <button type="button"  
                                        data-user-id="{{ $user->id }}" 
                                        data-user-name="{{ $user->name }}"                                   
                                        class="delete-user btn btn-outline-info btn-circle btn-lg btn-circle ml-2"><i
                                            class="fa fa-trash"></i> </button>
                                </td>
                            </tr>
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

