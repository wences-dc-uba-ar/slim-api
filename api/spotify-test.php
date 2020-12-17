<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/spotify.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();


// $result = $sp->search('metallica', 15, 'artist,album,playlist,track');
// file_put_contents('search.json', json_encode($result, JSON_PRETTY_PRINT));
$result = Spotify::search('metallica', 1, 'artist');

var_export($result);

// $result = $sp->getAlbums();
// $sp->getArtistData('metallica');
