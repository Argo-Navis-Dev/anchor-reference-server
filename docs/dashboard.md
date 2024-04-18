# Dashboard
The Anchor Reference Server dashboard enables you to manage Anchor users (agents) and customers. It allows you to validate the customer-submitted KYC data (fields). When their status changes, they are automatically notified by the SEP-12 callback mechanism.
Once the server is running and the database is created, you can access the system with the following credentials: \
**Username:** anchor.admin@argo-navis.dev\
**Password:** AnchorAdmin2023! \
Please change the password after the first login.
## User management 
By default, user registration is disabled. If you want to enable it, please open the [web.php](https://github.com/Argo-Navis-Dev/anchor-reference-server/blob/main/routes/web.php) file and modify the following line from:
```php
Auth::routes(['register' => false]);
```
to
```php
Auth::routes();
```
After successfully logging in, you can manually create, edit, and delete users.

## Customer management 
These views permit you to view, edit and validate the registered customers and their KYC data.