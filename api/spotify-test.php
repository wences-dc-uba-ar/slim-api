<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/Spotify.php';


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();


$result = Spotify::search('metallica', ['limit'=>1]);

echo("\n" . json_encode($result, JSON_PRETTY_PRINT));
echo("\n\n");

if(count($result['artists'])) {
    $artist_id = $result['artists'][0]['id'];

    $result = Spotify::getAlbums($artist_id);

    echo("\n" . count($result));

    echo("\n" . json_encode($result, JSON_PRETTY_PRINT));
}
