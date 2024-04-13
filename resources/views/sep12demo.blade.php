<!-- SEP-10 and SEP-12 demo ppage template -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sep 10 & 12 Demo</title>

    <!-- Freighter Wallet API -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/stellar-freighter-api/1.7.1/index.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/stellar-sdk/10.4.1/stellar-sdk.min.js"></script>

    <!-- FontAwesome -->
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" />

    @vite(['resources/sass/sep12_demo.scss', 'resources/js/sep12_demo.js'])

    <script>
    //Fill the network from the .env file
    var STELLAR_NETWORK = "{{ env('STELLAR_NETWORK') }}";
    </script>

</head>

<body class="antialiased sep12-demo pb-5">

    <!-- Page title -->
    <div class="container title-wrapper">
        <h1 class="h1 mb-3">
            <i class="fa fa-ship" aria-hidden="true"></i>
            <span class="ms-1">Anchor auth (SEP-10) and KYC (SEP-12) demo</span>
        </h1>
    </div>

    <!-- Subtitle which indicates the authenticated status -->
    <div id="authenticated-as-wrapper" class="container authenticated-as-wrapper">
        <h3 class="mb-3 ">Authenticated as</h3>
    </div>

    <!-- Authenticate form -->
    <div id="authenticate-wrapper" class="container authenticate-wrapper">
        <h3 class="py-1 text-left">Authenticate</h3>
        <div class="input-group">
            <input id="account-id" type="text" class="form-control" placeholder="Stellar Account ID">
            <button id="retrieve-jwt-token-btn" type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>

    <!-- Registration/update form -->
    <div id="registration-form-wrapper" class="container registration-form-wrapper mt-5">
        <form id="registration-form" class="registration-form" method="PUT" enctype="multipart/form-data">
            <h3 class="py-1 text-left">Register</h3>

            <div class="mb-3">
                <input id="first_name" name="first_name" type="text" class="form-control" placeholder="First name">
            </div>

            <div class="mb-3">
                <input id="last_name" name="last_name" type="text" class="form-control" placeholder="Last name">
            </div>

            <div class="mb-3">
                <input id="email_address" name="email_address" type="text" class="form-control"
                    placeholder="Email address">
            </div>

            <div class="mb-3">
                <input id="id_number" name="id_number" type="text" class="form-control" placeholder="ID number">
            </div>

            <div class="mb-3">
                <label for="id_type" class="mb-2">ID type, select an option</label>
                <select class="form-select" name="id_type">
                    <option selected value="id_card">ID Card</option>
                    <option value="passport">Passport</option>
                    <option value="drivers_license">Drivers Licence</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="photo_id_front" class="mb-2">Image of front of user's photo ID or passport</label>
                <input type="file" class="form-control" id="photo_id_front" name="photo_id_front">
            </div>

            <div class="mb-3">
                <label for="photo_id_back" class="mb-2">Image of back of user's photo ID or passport</label>
                <input type="file" class="form-control" id="photo_id_back" name="photo_id_back">
            </div>
            <div class="mb-3">
                <button id="registration-btn" class="btn btn-primary registration-btn">Submit</button>
            </div>
        </form>
    </div>

    <!-- Verification form -->
    <div id="verification-form-wrapper" class="container verification-form-wrapper">
        <h3 class="py-1 text-left">Verify your email address</h3>
        <label for="verification-code" class="mb-2">Check your mailbox for the verification code</label>
        <div class="input-group">
            <input id="verification-code" name="verification-code" type="text" class="form-control"
                placeholder="Enter the verification code">
            <button id="verify-btn" type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>

    <!-- Authenticate form -->
    <div id="callback-wrapper" class="container callback-wrapper mt-5">
        <h3 class="py-1 text-left">Set customer callback URL</h3>
        <div class="input-group">
            <input id="callback" type="text" class="form-control" placeholder="Customer callback URL">
            <button id="save-customer-callback-btn" type="submit" class="btn btn-primary">Submit</button>
        </div>
    </div>

    <!-- Customer info -->
    <div id="customer-info-wrapper" class="container customer-info-wrapper mt-5 p-0">
        <div class="container ">
            <div class="customer-info-inner-wrapper p-4">
                <h3 class="py-1 text-left">Customer info
                    <i class="fa fa-refresh" cursor: pointer;" aria-hidden="true"></i>
                </h3>

                <span class="">ID: </span><span id="customer-id-val"></span> <br>
                <span class="">Status: </span><span id="customer-status-val" class="status-text flash-text"></span>
                <h4 class="mt-3">Provided fields</h4>

                <table id="provided-fields-table" class="table">
                    <thead>
                        <tr>
                            <th scope="col">Field name</th>
                            <th scope="col">Description</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>

                <div id="missing-fields-wrapper">
                    <h4 class="mt-3">Missing fields</h4>
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
                    <button id="update-btn" class="btn btn-success update-btn">Update customer</button>
                    <button id="delete-btn" class="btn btn-danger ms-2">Delete customer</button>
                </div>

            </div>
        </div>
    </div>

        <!-- Generic info dialog -->
        <div id="info-dialog" class="modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Info</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-primary">OK</button>
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