# PickleWeb

![Pickle Logo](https://raw.githubusercontent.com/FriendsOfPHP/pickle_logo/master/pickle.png)

Lite version of packagist, used for PHP extensions via [Pickle](https://github.com/FriendsOfPhp/pickle) or as a simple
index for PHP extensions.

Aims to index PHP, HHVM or other implementations' extensions, according to pickle goals.

## TODOs

Github authentification done, package registration&validation in progress, layout, logo and UI done for the general design. Best way to contribute and know what has to be done is to follow the issues and create a PR :)

We will try to keep up-to-date as much as possible :)

## Setup

```
composer install
```

### OAuth

To properly test the application, you will have to login using OAuth and one of the three supported providers. To do so,
you will have to create a test application. For example, on Github:

* Go to [https://github.com/settings/applications](https://github.com/settings/applications)
* And click on [Register new application](https://github.com/settings/applications/new)
* Fill in the "Application name" field with a descriptive name, for example "pickleweb-dev"
* Set the "Homepage URL" to `http://localhost:8080` (this URL depends on the method you chose to run the application, [PHP](#php-built-in-server) or [Docker](#docker))
* Set the "Authorization callback URL" to `http://localhost:8080/login/github` (this URL depends on the method you chose to run the application, [PHP](#php-built-in-server) or [Docker](#docker))

Once done, click on the "Register Application" button to get the client ID and secret for your application.

Then, copy `src/config.json.example` to `src/config.json` and fill you ID and secret in the proper section.

You are now ready to [run](#running) the Pickle Web application and use authentication.

## Running

### PHP built-in server

```
php -S 0.0.0.0:8080 -t web
```

On Windows this might under circumstances require administrative privileges to work properly. In any case, the credentials under which PHP is running need to allow symlinking.


### Docker

First, install [`docker`](https://docs.docker.com/installation/) (or [`boot2docker`](http://boot2docker.io/)) and
[`docker-compose`](https://docs.docker.com/compose/install/) then run the following command:

```
docker-compose up
```

PickleWeb will then be available at `http://localhost:8080`.

*If you are using [`boot2docker`](http://boot2docker.io/), you will have to replace `localhost` with the IP of your
boot2docker VM (you can get it by running `boot2docker ip`).*
