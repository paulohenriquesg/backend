## User
* `php artisan app:user:create test2@test.com password123` create a user

## Development

### Before
```shell
cp .env.example .env
touch database/queues-sqlite.sqlite
touch database/database.sqlite
composer install
php artisan key:generate
php artisan migrate --seed
php artisan migrate --database=queues-sqlite --path=database/migrations/queues
pnpm build
```

### Server
* `php artisan serve`
* `pnpm dev`
* `pnpm build`

### Queues
* `php artisan queue:listen` run a scheduled jobs listener

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


