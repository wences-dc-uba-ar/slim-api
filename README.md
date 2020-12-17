# Slim-api

api test con slim

## How to test

-   install dependencies

```bash
composer install
```

-   copy .env-example file as .env

```bash
cp .env-example .env
```

-   edit .env with SPOTIFY_CLIENT_ID and SPOTIFY_CLIENT_SECRET from your app

-   start server

```bash
php --server=0.0.0.0:8080 api/index.php
```

-   open web browser http://localhost:8080

## Where get spotify credentials

-   login and create an spotify app in https://developer.spotify.com/dashboard/applications

-   get the `Client Secret` and `Client Id` from the app details page

## Todo

-   reubicar funcionalidad segun slim

-   unit testing
