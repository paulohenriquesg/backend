# FilesNest server (backend)

`A featureless, self-hosted iCloud photos and videos backup server.`


FilesNest is a file storage server that allows you to backup your iCloud photos and videos. It is designed to be self-hosted, and easy to use.

[![GitHub License](https://img.shields.io/github/license/files-nest/backend?style=flat-square&labelColor=black&)](https://github.com/files-nest/backend/LICENSE.md)
[![CI](https://img.shields.io/github/actions/workflow/status/files-nest/backend/ci.yml?branch=main&label=CI&style=flat-square&labelColor=black)](#)
[![GitHub Issues](https://img.shields.io/github/issues/files-nest/backend?style=flat-square&labelColor=black)](https://github.com/files-nest/backend/issues)
[![GitHub Stars](https://img.shields.io/github/stars/files-nest/backend?style=flat-square&labelColor=black&color=ffcb47)](https://github.com/files-nest/backend/stargazers)

## 👋🏻 Getting Started

> \[!IMPORTANT]
>
> This project is still at a very early stage. Use it at your own risk! **Star Us** to receive notifications about new bugfixes and features from GitHub ⭐️

### Prerequisites
* [Docker](https://docs.docker.com/get-docker/)
* [Docker Compose](https://docs.docker.com/compose/install/)

or

* [Portainer](https://docs.portainer.io/)

### Self Hosting
To self-host FilesNest, you can use the provided `docker-compose.yml` file. This file contains all the necessary configurations to run the application in a Docker container.

The Docker Compose setup includes:

- FilesNest server container
- Queue
- Automatic database migrations
- Volume mounts for persistent data storage and database

Steps:

* Copy [docker-compose.yml](https://raw.githubusercontent.com/files-nest/backend/refs/heads/main/docker-compose.yml) file.
* Replace `YOUR_STORAGE_PATH_HERE` by your desired storage path.
* Replace `YOUR_DATABASE_PATH_HERE` by your desired database path.
* Change `9999` port to your desired port.
* If it's a first run generate an application key by running `docker compose exec files-nest make generate-key`. Save the generated key. Changing it will invalidate existing encrypted data, causing issues with user data and stored information.
* Set up the `APP_KEY` environment variable. Example `APP_KEY=base64:ngsvSN2tyM1kjTWCka1IjkyuNMqMlpyHhgTj36KEJDs=`
* Run `docker compose up -d` to start the application.


#### Environment Variables
Configure FilesNest to suit your needs with the following environment variables:

| Variable | Required | Description | Example |
|----------|----------|-------------|---------|
| `APP_KEY` | Yes | Application encryption key. Generate with make generate-key. | `base64:key=` |
| `PUID` | No | User ID for file ownership inside the container. Set to your host user ID. | `1000` |
| `PGID` | No | Group ID for file ownership inside the container. Set to your host group ID. | `1000` |
| `POST_MAX_SIZE` | No | Sets the maximum allowed size for upload requests. The iOS app uses this value to determine file chunk sizes. | `100M` default |
| `MEMORY_LIMIT` | No | Memory limit for worker processes. Increase this value if you raise `POST_MAX_SIZE`. | `150M` default |
| `APP_DEBUG` | No | Toggle debug mode (`true` or `false`). When enabled, detailed debugging logs are shown. | `false` (default) |
| `TZ` | No | Timezone for the container (affects logs). | `Europe/Berlin` |
| `IMPORT_HTTP_LOG` | No | Enable HTTP requests logging. | `import /app/docker/http_log.caddy` |


### Create a user

> \[!NOTE]
> From this moment on, we are going to assume your server is at 127.0.0.1, port 9999.

There are two ways to create a new user:
* Using the web interface:
    - accessing the `/register` URL, like http://127.0.0.1:9999/register
* Using the command line (inside of the server container):
    - `make create-user`

### Security
When self-hosting, follow standard security best practices:

* Do not expose your server directly to the public internet. Restrict access to trusted networks or use a VPN.
* Use a reverse proxy to handle HTTPS and forward requests to FilesNest.
* Keep your server and dependencies up to date with security patches.
* Use strong, unique passwords for all user accounts.
* Limit open ports and firewall access to only what is necessary.
* Regularly back up your data and configuration.
* Consider using additional security layers such as Cloudflare or similar services.

### Backup
* FilesNest is intentionally designed to be very simple: it stores your photos and videos in a local storage folder, organized by email, year, and month.
* Metadata about your files is kept in a local SQLite database. 
* This tool does not include built-in support for cloud backups or external storage providers like S3. 
* If you want to back up your files to services such as Amazon S3 or other cloud storage, you will need to use additional tools or services to sync or copy your storage folder to your preferred backup destination.

## ⌨️ Local Development

### Docker
* `docker compose -f docker-compose.development.yml up -d`

```shell
cp .env.example .env
touch database/mount/queues-sqlite.sqlite
touch database/mount/database.sqlite
make dev/composer-install
make dev/generate-key
make dev/migrate
```

### User
* `make dev/create-user` create a user

### Queues
* `make dev/queue-listen` run a scheduled jobs listener

### Login
Redirect should be whitelisted in `REDIRECT_URLS_WHITELIST` environment variable. It should be a comma-separated list of URLs.
```dotenv
REDIRECT_URLS_WHITELIST=http://127.0.0.1:9999,http://localhost:9999
```

* Open the following link in your browser http://127.0.0.1:9999/login?redirect=http://127.0.0.1:9999&device_name=browser
* Login with the user created above.
* A token could be found in a URL after the redirect.


