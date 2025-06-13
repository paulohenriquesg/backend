## User
* `php artisan app:user:create test2@test.com password123` create a user

## Development

### Before
```shell
cp .env.example .env
touch database/mount/queues-sqlite.sqlite
touch database/mount/database.sqlite
composer install
php artisan key:generate
php artisan migrate --seed
php artisan migrate --database=queues-sqlite --path=database/migrations/queues
pnpm build
```

### Server
* `php artisan serve`
* `pnpm install`
* `pnpm dev`
* `pnpm build`

### Docker
* `docker compose -f docker-compose.development.yml up`

### Queues
* `php artisan queue:listen` run a scheduled jobs listener

### Login
Redirect should be whitelisted in `REDIRECT_URLS_WHITELIST` environment variable. It should be a comma-separated list of URLs.
```dotenv
REDIRECT_URLS_WHITELIST=http://127.0.0.1:8080,http://localhost:8080
```

* Open the following link in your browser http://127.0.0.1:8080/login?redirect=http://127.0.0.1:8080&device_name=browser
* Login with the user created above.
* A token could be found in a `token` cookie after the redirect.


