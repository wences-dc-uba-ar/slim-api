<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
});

$app->get('/var/{nombre}', function (Request $request, Response $response, $args) {
    $nombre = $args['nombre'];
    $response->getBody()->write("Hello $nombre");
    return $response;
});



$app->get('/api/v1/albums', function (Request $request, Response $response, $args) {
    $banda = $request->getQueryParams()['q'];

    try {
        // spotify

        # auth
        $clientID = '';
        $clientSecret = '';
        // Base 64 encoded string that contains the client ID and client secret key.
        $header = "Authorization: Basic " . base64_encode("$clientID:$clientSecret");
        $bodyParams = ["grant_type"=>"client_credentials"];
        $authURI = 'https://accounts.spotify.com/api/token';

        # artist


        # albums
        // 'https://api.spotify.com/v1/artists/1vCWHaC5f2uS3yhpwWbIA6/albums?market=ES&limit=100');
        //  -H "Accept: application/json" -H "Content-Type: application/json" -H

        $data = json_decode($text_data);
    } catch (\Throwable $th) {
        $data = ["Esception" => $th->getMessage()];
    }



    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
