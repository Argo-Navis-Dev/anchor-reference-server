<?php

namespace App\Stellar;

use ArgoNavis\PhpAnchorSdk\config\IAppConfig;
use Soneso\StellarSDK\Network;

class StellarAppConfig implements IAppConfig
{

    /**
     * @inheritDoc
     */
    public function getStellarNetwork(): Network
    {
        return new Network(config('stellar.app.network_passphrase', 'Test SDF Network ; September 2015'));
    }

    /**
     * @inheritDoc
     */
    public function getHorizonUrl(): string
    {
        return config('stellar.app.horizon_url', 'https://horizon-testnet.stellar.org');
    }

}
