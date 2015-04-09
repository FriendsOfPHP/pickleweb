# PickleWeb

Lite version of packagist, used for PHP extensions via [Pickle](https://github.com/FriendsOfPhp/pickle) or as a simple
index for PHP extensions.

Aims to index PHP, HHVM or other implementations' extensions, according to pickle goals.

## TODOs

**Nothing done yet, see the issues for the TODOs.**

We will try to keep up-to-date as much as possible. For now only the skeleton has been created, open early :)

## Setup

```
composer install
```

## Running

### PHP built-in server

```
php -S 0.0.0.0:8080 -t web
```

### Docker

First, install [`docker`](https://docs.docker.com/installation/) (or [`boot2docker`](http://boot2docker.io/)) and
[`docker-compose`](https://docs.docker.com/compose/install/) then run the following command:

```
docker-compose up
```

PickleWeb will then be available at `http://localhost:8080`.

*If you are using [`boot2docker`](http://boot2docker.io/), you will have to replace `localhost` with the IP of your
boot2docker VM (you can get it by running `boot2docker ip`).*
