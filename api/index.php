<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use GuzzleHttp\Client;

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
        $client = new GuzzleHttp\Client();
        # auth
        $clientID = '364ed9a5a2b04f0ab439e0efa43a28bc';
        $clientSecret = 'b877942d56af4e9e9f2434022d106009';

        $response = $client->post('https://accounts.spotify.com/api/token', [
            'headers' => [
                "Authorization"=>"Basic " . base64_encode("$clientID:$clientSecret"),
                // "Accept"=>"application/json",
                // "Content-Type"=>"application/json",
            ],
            'form_params'=> ["grant_type"=>"client_credentials"]
        ]);
        $headers = $response->getHeaders();
        $date = $headers['date'];
        $content_type = $headers['content-type'];
        unset($headers['date']);
        unset($headers['content-type']);
        $data = [
            'status-code' => $response->getStatusCode(),
            'body' => $response->getBody(),
            'date' => $date,
            'content_type' => $content_type,
            'headers' => $headers,
        ];


        # artist


        # albums
        // 'https://api.spotify.com/v1/artists/1vCWHaC5f2uS3yhpwWbIA6/albums?market=ES&limit=100');

        // $data = @json_decode($result);
        // if(!$data) {
        //     $data = ['raw response'=>$result];
        // }
    } catch (\Throwable $th) {
        $data = ["Exception" => $th->getMessage()];
    }



    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
