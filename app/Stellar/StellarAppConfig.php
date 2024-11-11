<?php

namespace App\Stellar;

use ArgoNavis\PhpAnchorSdk\config\IAppConfig;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Network;

use function json_encode;

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

    public function getLocalizedText(
        string $key,
        ?string $locale = 'en',
        ?string $default = null,
        ?array $params = [],
    ): string {
        if ($params === null) {
            $params = [];
        }
        $localizedText = __($key, $params, $locale);
        if ($localizedText === $key) {
            $localizedText = $default ?? $key;
        }
        Log::info(
            'Fetching the localized text by the SDK.',
            ['localized_text' => $localizedText, 'key' => $key, 'locale' => $locale, 'default' => $default,
                'params' => json_encode($params),
            ],
        );

        return $localizedText;
    }
}
