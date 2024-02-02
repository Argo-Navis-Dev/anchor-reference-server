# anchor-reference-server

The PHP Stellar Anchor SDK makes it easier for PHP developers to implement Stellar Anchors.

Stellar clients make requests to the endpoints of Anchor Servers using sets of standards called [SEPs](https://developers.stellar.org/docs/fundamentals-and-concepts/stellar-ecosystem-proposals) (Stellar Ecosystem Proposals). The PHP Anchor SDK will help PHP developers to implement the Client - Anchor interaction by abstracting the Stellar-specific functionality defined in the SEP's so that developers can focus on business logic.

The SDK is composed of two components:
- A [Service Layer Library](https://github.com/Argo-Navis-Dev/php-anchor-sdk) implementing the Stellar specific functionality described in the corresponding SEPs
- An Anchor Reference Server implementation that uses the library.


This is the repo of the Anchor Reference Server. Pls. see [architecture doc](https://github.com/Argo-Navis-Dev/php-anchor-sdk/blob/main/docs/architecture.md).

The implementation of the Service Layer Library can be found [here](https://github.com/Argo-Navis-Dev/php-anchor-sdk). 

## Roadmap:

- Implementation of [SEP-01](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0001.md) Service (Stellar Info File) until Dec.10.2023 -> Done
- Implementation of [SEP-10](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0010.md) Service (Stellar Authentication) until Dec.27.2023 -> Done
- Implementation of [SEP-12](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0012.md) KYC API Service &  [SEP-09](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0009.md) Standard KYC Fields -> Done
- Implementation of [SEP-24](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0024.md) Hosted Deposit and Withdrawal - Interactive Flow Service -> in progress
- Implementation of [SEP-31](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0031.md) Cross-Border Payments Service
- Implementation of [SEP-38](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0038.md) Anchor RFQ Service
- Implementation of [SEP-06](https://dashboard.communityfund.stellar.org/redirect?url=https%3A%2F%2Fgithub.com%2Fstellar%2Fstellar-protocol%2Fblob%2Fmaster%2Fecosystem%2Fsep-0006.md) Deposit and Withdrawal Service


## Run the server locally:

1. Clone this repo from GitHub.

2. Install the dependencies: 

`php composer install`

3. Create your `.env` file as shown in [.env.example](https://github.com/Argo-Navis-Dev/anchor-reference-server/blob/main/.env.example)

4. Start the server:

`php artisan serve` - The server will run on `http://localhost:8000`

5. Start vite:

`npm run dev` - Now the server accepts requests at: `https://localhost:5173`

6. Run the db migration and seed the data:

`php artisan migrate:refresh --seed`

7. Run the [StellarCustomerTest](https://github.com/Argo-Navis-Dev/anchor-reference-server/blob/main/tests/Unit/StellarCustomerTest.php) cases - The test uses SEP-01, SEP-10, SEP-09 and SEP-12 functionality. 
