<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Spotify.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();


$result = Spotify::search('hermetica', 1);

// echo(json_encode($result, JSON_PRETTY_PRINT));

if(count($result['artists'])) {
    $artist_id = $result['artists'][0]['id'];

    $result = Spotify::getAlbums($artist_id);

    echo(json_encode($result, JSON_PRETTY_PRINT));
}
