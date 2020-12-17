<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/spotify.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/..');
$dotenv->load();


$sp = Spotify::getInstance();

// $result = $sp->search('metallica', 15, 'artist,album,playlist,track');
// file_put_contents('search.json', json_encode($result, JSON_PRETTY_PRINT));
$result = $sp->search('metallica', 1, 'artist');

var_export($result['artists']);

// $result = $sp->getAlbums();
// $sp->getArtistData('metallica');
