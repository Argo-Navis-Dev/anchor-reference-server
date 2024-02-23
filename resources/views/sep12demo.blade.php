<!-- SEP-10 and SEP-12 demo ppage template -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sep 10 & 12 Demo</title>


    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    @vite('resources/css/sep12_demo.css')
    @vite('resources/js/sep12_demo.js')

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/stellar-freighter-api/1.7.1/index.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/stellar-sdk/10.4.1/stellar-sdk.min.js"></script>

    <link rel="icon" href="https://argo-navis.dev/favicon.ico">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <script>
        //Fill the network from the .env file
        var STELLAR_NETWORK = "{{ env('STELLAR_NETWORK') }}";            
    </script>

</head>

<body class="antialiased sep12-demo">

    <!-- Page title -->
    <div class="container title-wrapper">
        <h1 class="h1 mb-3 font-weight-normal p-3">
            <p class="mr-2"><i class="fa fa-ship" style="color:#ff7400" aria-hidden="true"></i></p>Anchor auth (SEP-10)
            and KYC (SEP-12) demo
        </h1>
    </div>

    <!-- Subtitle which indicates the authenticated status -->
    <div id="authenticated-as-wrapper" class="container authenticated-as-wrapper">
        <h3 class="h3 mmb-3 font-weight-normal p-3">Authenticated as</h3>
    </div>

    <!-- Authenticate form -->
    <div id="authenticate-wrapper" class="container authenticate-wrapper">
        <div class="container">
            <h3 class="h3 mb-3 font-weight-normal">Authenticate</h3>
        </div>
        <div class="form-group col-12">
            <label for="inputField">Stellar Account ID</label>
            <input type="text" class="form-control" id="account-id" name="inputField" required value="">
        </div>
        <div class="form-group col-12">
            <button id="retrieve-jwt-token-btn" class="btn btn-primary">Submit</button>
        </div>
    </div>

    <!-- Registration/update form -->
    <div id="registration-form-wrapper" class="container registration-form-wrapper mt-5">
        <form id="registration-form" class="registration-form" method="PUT" enctype="multipart/form-data">
            <div class="container">
                <h3 class="h3 mb-3 font-weight-normal">Register</h3>
            </div>
            <div class="form-group col-12">
                <input id="first_name" type="text" class="form-control" name="first_name" placeholder="First name">
            </div>

            <div class="form-group col-12">
                <input id="last_name" type="text" class="form-control" name="last_name" placeholder="Last name">
            </div>
            <div class="form-group col-12">
                <input id="email_address" type="text" class="form-control" name="email_address"
                    placeholder="Email address">
            </div>

            <div class="form-group col-12">
                <input id="id_number" type="text" class="form-control" name="id_number" placeholder="ID number">
            </div>
            <div class="form-group col-12">
                <label for="combo_box">ID type, select an option</label>
                <select class="form-control" id="combo_box" name="id_type">
                    <option value="id_card">ID Card</option>
                    <option value="passport">Passport</option>
                    <option value="drivers_license">Drivers Licence</option>
                </select>
            </div>
            <div class="form-group col-12">
                <label for="photo_id_front">Image of front of user's photo ID or passport</label>
                <input type="file" class="form-control" id="photo_id_front" name="photo_id_front"
                    placeholder="photo_id_front">
            </div>
            <div class="form-group col-12">
                <label for="photo_id_back">Image of back of user's photo ID or passport</label>
                <input type="file" class="form-control" id="photo_id_back" name="photo_id_back"
                    placeholder="photo_id_back">
            </div>
            <div class="form-group col-12">
                <button id="registration-btn" class="btn btn-primary registration-btn">Submit</button>
            </div>
        </form>
    </div>

    <!-- Verification form -->
    <div id="verification-form-wrapper" class="container verification-form-wrapper mt-5">
        <div class="container">
            <h3 class="h3 mb-3 font-weight-normal">Verify your email address</h3>
        </div>
        <div class="form-group col-12">
            <label for="inputField">Check your mailbox for the verification code</label>
            <input type="text" class="form-control" id="verification-code" name="inputField"
                placeholder="Enter the verification code" required>
        </div>
        <div class="form-group col-12">
            <button id="verify-btn" class="btn btn-primary">Submit</button>
        </div>
    </div>

    <!-- Customer info -->
    <div id="customer-info-wrapper" class="container customer-info-wrapper mt-5">
        <div class="row">
            <div class="col-12">
                <div class="container">
                    <div class="customer-info-inner-wrapper p-4">
                        <h3 class="h3 mb-3 font-weight-normal">Customer info
                            <i class="fa fa-sync ml-2" style="color:#ff7400; cursor: pointer;" aria-hidden="true"></i>
                        </h3>
                        <span class="">ID: </span><span id="customer-id-val"></span> <br>
                        <span class="">Status: </span><span id="customer-status-val"
                            class="status-text flash-text"></span>
                        <h4 class="h4 font-weight-normal  mt-3">Provided fields</h4>
                        <table id="provided-fields-table" class="table">
                            <thead>
                                <tr>
                                    <th>Field name</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        <div id="missing-fields-wrapper">
                            <h4 class="h4 font-weight-normal  mt-3">Missing fields</h4>
                            <table id="missing-fields-table" class="table">
                                <thead>
                                    <tr>
                                        <th>Field name</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div class="form-group col-12">
                            <button id="update-btn" class="btn btn-info update-btn">Update customer</button>
                            <button id="delete-btn" class="btn btn-danger ml-2">Delete customer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generic info dialog -->
    <div class="modal" tabindex="-1" role="dialog" id="info-dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Success</h3>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div id="loading-overlay" class="overlay">
        <div class="spinner"></div>
    </div>

</body>

</html>