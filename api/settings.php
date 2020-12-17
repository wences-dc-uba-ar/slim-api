<?php

return [
    'spotifyClientID' => getenv('SPOTIFY_CLIENT_ID'),
    'spotifyClientSecret' => getenv('SPOTIFY_CLIENT_SECRET'),
    'displayErrorDetails' => (bool)getenv('DISPLAY_ERRORS'),
];
