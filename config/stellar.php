<?php

return [
    // IAppConfig values
    'app' => [
        /*
        |--------------------------------------------------------------------------
        | Stellar App: Network
        |--------------------------------------------------------------------------
        |
        | Network to use. For example 'testnet', 'public', or your custom network name.
        |
        */
        'network' => 'testnet',
        /*
        |--------------------------------------------------------------------------
        | Stellar App: Horizon url
        |--------------------------------------------------------------------------
        |
        | Url of Horizon to be used. E.g. 'https://horizon-testnet.stellar.org'.
        |
        */
        'horizon_url' => 'https://horizon-testnet.stellar.org',
    ],

    // ISep10Config values
    'sep10' => [
        /*
        |--------------------------------------------------------------------------
        | SEP-10: Web auth domain
        |--------------------------------------------------------------------------
        |
        | The `web_auth_domain` property of <a
        | href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md#response">SEP-10</a>.
        | If the `web_auth_domain` is not specified (value null), the `web_auth_domain` will be set to the first value
        | of `home_domains`. If given, the `web_auth_domain` value must equal to the host of the SEP server.
        |
        */
        'web_auth_domain' => 'localhost',

        /*
        |--------------------------------------------------------------------------
        | SEP-10: Home domains
        |--------------------------------------------------------------------------
        |
        | The `home_domains` property of <a
        | href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md#request">SEP-10</a>.
        | Must be provided and can not be null.
        |
        */
        'home_domains' => [
            'localhost:5173'
        ],
        /*
        |--------------------------------------------------------------------------
        | SEP-10: Auth Timeout
        |--------------------------------------------------------------------------
        |
        | Set the authentication challenge transaction timeout in seconds.
        | An expired signed transaction will be rejected. This is the timeout period the client must
        | finish the authentication process. (ie: sign and respond the challenge transaction).
        |
        */
        'auth_timeout' => 900,

        /*
        |--------------------------------------------------------------------------
        | SEP-10: Server signing seed
        |--------------------------------------------------------------------------
        |
        | The server signing seed for Stellar Web Auth that will sign the auth challenge transaction.
        | E.g.'SADVE2GUZH6F4KNTHJW4PHE7D5S6HBOLEUQFNGQQV7CRNEPTCNSJBGMP'
        | The corresponding account id (public key) must be exposed in the stellar toml file as 'SIGNING_KEY'.
        | E.g. GDFBQM4TOOCGHH63PFQVAPIMVOB5UMETRXI2LJXRJBX2FPH6S62BHYVC
        */
        'server_signing_seed' => env('STELLAR_SIGNING_KEY'),

        /*
        |--------------------------------------------------------------------------
        | SEP-10: JWT signing key
        |--------------------------------------------------------------------------
        |
        | The key used to sign the jwt. Can be for example a stellar secret seed. E.g.
        | 'SC6NANPAEQZ23HQSEB5JY6NXX5LYKJGSRTT2EKGKYIOZVI73NPP47ONG'
        */
        'jwt_signing_key' => env('STELLAR_JWT_SIGNING_KEY'),

        /*
        |--------------------------------------------------------------------------
        | SEP-10: JWT timeout
        |--------------------------------------------------------------------------
        |
        | Set the timeout in seconds of the authenticated JSON Web Token. An expired JWT will be
        | rejected. This is the timeout period after the client received the SEP-10 authentication challenge
        | challenge transaction we can not use the current timestamp to generate the jwt token's issued at value.
        | Therefore, we must use the timestamp from the challenge transaction. So the value returned here
        | should be higher than the authentication challenge transaction timeout.
        |
        */
        'jwt_timeout' => 3600,

        /*
        |--------------------------------------------------------------------------
        | SEP-10: Client attribution required
        |--------------------------------------------------------------------------
        |
        | Set true if the client attribution is required. Client Attribution requires clients to verify their
        | identity by passing a domain in the challenge transaction request and signing the challenge.
        | with the ``SIGNING_KEY`` on that domain's SEP-1 stellar.toml. See the SEP-10 section `Verifying
        | Client Application Identity` for more information (<a
        | href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md#verifying-client-application-identity">SEP-10</a>).
        | # # If the client_attribution_required is set to true, the list of allowed clients must be
        | configured in the `clients` # section of this configuration file. The `domain` field of the
        | client must be provided.
        |
        */
        'client_attribution_required' => false,

        /*
        |--------------------------------------------------------------------------
        | SEP-10: Allowed client domains
        |--------------------------------------------------------------------------
        |
        | If the client attribution is required (see: client_attribution_required). List here the allowed client
        | domains as strings within an array. If not required, you can set null as value.
        |
        */
        'allowed_client_domains' => null,

        /*
        |--------------------------------------------------------------------------
        | SEP-10: Known custodial accounts
        |--------------------------------------------------------------------------
        |
        | If you want to limit SEP 10 authentication to a list of known accounts you can list them
        | here as strings (account id) within an array. If not, you can set null as value.
        |
        */
        'known_custodial_accounts' => null,
    ]

];
