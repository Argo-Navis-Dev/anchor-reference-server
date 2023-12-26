<?php

namespace App\Stellar;

use ArgoNavis\PhpAnchorSdk\config\IAppConfig;
use Soneso\StellarSDK\Network;

class StellarAppConfig implements IAppConfig
{

    public function getStellarNetwork(): Network
    {
        $networkName = config('stellar.app.network', 'testnet');
        if ('testnet' == $networkName) {
            return Network::testnet();
        } else if ('public' == $networkName || 'main' == $networkName) {
            return Network::public();
        } else if ('futurenet' == $networkName) {
            return Network::futurenet();
        }
        // else your custom network

        return Network::testnet();
    }

    public function getHorizonUrl(): string
    {
        return config('stellar.app.horizon_url', 'https://horizon-testnet.stellar.org');
    }

}
