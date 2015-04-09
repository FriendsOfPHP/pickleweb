lite version of packagist, use for PHP extensions via Pickle (https://github.com/FriendsOfPhp/pickle) or as simple
index for PHP extensions.

Aims to index PHP, HHVM or other implementations extensions, according 
to pickle goals.

Nothing done yet, see the issues for the TODOs. We will try to keep uptodate as much as possible. For now only the skeleton has been created, open early :)

Setup:
composer install
use ./web as document_root

## Working with docker

First, [install docker](https://docs.docker.com/installation/) and [`docker-compose`](https://docs.docker.com/compose/install/)
and then, run the following command:

```
docker-compose up
```

PickleWeb will then be available at `http://localhost:8080`.

*If you are using [boot2docker](), you will have to replace `localhost` with the IP of your boot2docker VM*
