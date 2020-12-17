<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/spotify.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();


$app = AppFactory::create();
$container = $app->getContainer();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
// $errorMiddleware->setDefaultErrorHandler($container->get(DefaultErrorHandler::class));


$app->get('/api/v1/albums', function (Request $request, Response $response, $args) {

    try {
        if(empty($request->getQueryParams()['q'])) {
            throw new Exception('missing "q" GET prameter (Artist Name)', 400);
        }
        $name = $request->getQueryParams()['q'];

        $search = Spotify::search($name);

        if(!count($search['artists'])) {
            throw new Exception("Artist '$name' not found", 404);
        }

        $artist_id = $search['artists'][0]['id'];

        $data = Spotify::getAlbums($artist_id);

    } catch (\Throwable $th) {
        $data = [
            "error code" => $th->getCode(),
            "message" => $th->getMessage(),
        ];
        $response = $response->withStatus($th->getCode(), 'there where some errors');
    }

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});



$app->get('/api/v1/search/{name}', function (Request $request, Response $response, $args) {

    try {
        if(empty($args['name'])) {
            throw new Exception('missing "name" in url path /api/v1/search/{name}', 400);
        }

        $data = Spotify::search($args['name'], [
            'limit' => 50
            ]);

        if(!count($data['artists'])) {
            throw new Exception("Artist '{$args['name']}' not found", 404);
        }

    } catch (\Throwable $th) {
        $data = [
            "error" => $th->getCode(),
            "message" => $th->getMessage(),
        ];
        $response = $response->withStatus($th->getCode(), 'there where some errors');
    }

    $response->getBody()->write(json_encode($data, JSON_PRETTY_PRINT));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->get('/{path:.+}', function ($request, $response, array $args) {
    global $patterns;
    $response->getBody()->write(json_encode([
        'error' => 404,
        'message' => 'invalid route',
        'try' => [
            'get artist albums' => '/api/v1/albums?q={name  }',
            'search artist' => '/api/v1/search/{name}',
        ],
    ], JSON_PRETTY_PRINT));

    $response = $response->withStatus(404, 'not found');

    return $response->withHeader('Content-Type', 'application/json');
});


$app->run();
