# Admin Interface Overview

The Anchor Reference Server admin interface provides a user-friendly platform to manage Anchor users (agents), customers, transactions, and configurations.

## Key functionalities include:

- **KYC Management**: Validate and manage customer-submitted KYC data and track status changes. Customers are automatically notified of updates via the SEP-12 callback mechanism.
- **KYC Fields**: Create and manage SEP-12 types and fields.
- **Anchor Assets**: Create and manage Anchor assets.
- **Rates**: Define and manage SEP-38 rates.
- **Transaction Management**: View all transaction details (SEP-06, SEP-24, SEP-31, SEP-38 and SEP-08) and modify their status.

Once the server is running and the database is set up, access the dashboard using the following credentials:

**Username**: anchor.admin@argo-navis.dev  
**Password**: AnchorAdmin2023!

> **Note**: For security reasons, please change the default password immediately after your first login.

After logging in, you can manually create, edit, and delete user profiles as needed.

The admin interface is built using the [Filament framework](https://filamentphp.com/). For more information, refer to the [official documentation](https://filamentphp.com/docs).