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

* Copy [docker-compose.yml](https://raw.githubusercontent.com/files-nest/backend/refs/heads/main/docker-compose.yml) file.
* Set up `YOUR_STORAGE_PATH_HERE` to your desired storage path.
* Set up `YOUR_DATABASE_PATH_HERE` to your desired database path.
* Change `9999` port to your desired port.
* Run `docker compose up -d` to start the application.
* If it's a first run generate an application key by running `docker compose exec files-nest make generate-key`. Save the generated key. Changing it will invalidate existing encrypted data, causing issues with user data and stored information.
* Set up the `APP_KEY` environment variable. Example `APP_KEY=base64:ngsvSN2tyM1kjTWCka1IjkyuNMqMlpyHhgTj36KEJDs=`

### Create a user
* If it's a first user you can open a web page SERVER:PORT/register. For example, http://127.0.0.1:8080/register
* Or run `make create-user` from the server container

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
REDIRECT_URLS_WHITELIST=http://127.0.0.1:8080,http://localhost:8080
```

* Open the following link in your browser http://127.0.0.1:8080/login?redirect=http://127.0.0.1:8080&device_name=browser
* Login with the user created above.
* A token could be found in a URL after the redirect.


