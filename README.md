## User
* `php artisan app:user:create test2@test.com password123` create a user

## Development

### Before
* `touch database/queues-sqlite.db`
* `touch database/database.db`
* `php artisan migrate --seed`
* `php artisan migrate --database=queues-sqlite --path=database/migrations/queues`

### Server
* `php artisan serve`
* `pnpm dev`
* `pnpm build`

### Login
Redirect should be whitelisted in `config/app.php` file.
```php
'redirect_hosts' => [
    'http://127.0.0.1:8000',
],
```

* Open the following link in your browser http://127.0.0.1:8000/login?redirect=http://127.0.0.1:8000&device_name=iphone
* Login with the user created above.
* A token could be found in a `token` cookie after the redirect.


