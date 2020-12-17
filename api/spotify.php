<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;


require __DIR__ . '/../vendor/autoload.php';


class Spotify {

    const MAX_BATCH_SIZE = 50;  // spotify api max
    const ALL_HARD_LIMIT = 100;

    private static $instance = null;

    private $clientID = null;
    private $clientSecret = null;
    private $fs_token_file = null;
    private $access_token = null;
    private $client = null;

    public static function getInstance($clientID=null, $clientSecret=null) {
        if (self::$instance == null) {
            $clientID = $clientID ?: $_ENV['SPOTIFY_CLIENT_ID'];
            $clientSecret = $clientSecret ?: $_ENV['SPOTIFY_CLIENT_SECRET'];
            self::$instance = new Spotify($clientID, $clientSecret);
        }

        return self::$instance;
    }


    private function __construct($clientID, $clientSecret) {

        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->fs_token_file = sys_get_temp_dir() . '/spotify.token';
        $this->client = new GuzzleHttp\Client();
    }


    public static function search($keywords, $query=[]) {

        // https://developer.spotify.com/documentation/web-api/reference-beta/#category-search
        $uri = "https://api.spotify.com/v1/search";

        $response = self::getInstance()->request($uri, [
            'q' => $keywords,
            'limit' => $query['limit'] ?? 1,
            'offset' => $query['offset'] ?? null,
            'type' => $query['type'] ?? 'artist',
            'market' => $query['market'] ?? 'AR',
        ]);

        $result = [];
        foreach ($response as $atype => $typeResults) {
            if(isset($typeResults['items'])){
                $result[$atype] = [];
                foreach ($typeResults['items'] as $key => $value) {
                    $result[$atype][] = [
                        'id'=>$value['id'],
                        'name'=>$value['name'],
                    ];
                }
            }
        }

        return $result;
    }


    public static function getAlbums($artist_id, $query=[]) {

        // https://developer.spotify.com/documentation/web-api/reference-beta/#endpoint-get-an-artists-albums
        $uri = "https://api.spotify.com/v1/artists/$artist_id/albums";

        $response = self::getInstance()->requestAll($uri, [
            'limit' => $query['limit'] ?? null,
            'offset' => $query['offset'] ?? null,
            'market' => $query['market'] ?? 'AR',
            'include_groups' => $query['include_groups'] ?? 'album,single,appears_on,compilation'
        ]);

        $result = [];
        if(isset($response['items'])){
            foreach ($response['items'] as $album) {

                $best_cover = array_pop($album['images']);
                while(count($album['images'])) {
                    $a_cover = array_pop($album['images']);
                    if($a_cover['width'] > $best_cover['width']){
                        $best_cover = $a_cover;
                    }
                }

                switch ($album['release_date_precision']) {
                    case 'year':
                        $album['release_date'] .= '-01-01';
                        break;
                    case 'month':
                        $album['release_date'] .= '-01';
                        break;
                }

                $result[] = [
                    'name'=> $album['name'],
                    'released' => $album['release_date'],
                    'tracks' => $album['total_tracks'],
                    "cover" =>  $best_cover
                ];
            }
        }

        return $result;
    }


    private function requestAll($uri, $query=[], $extra=[], $method='GET', $batch_size=self::MAX_BATCH_SIZE) {

        $batch_size = min(self::MAX_BATCH_SIZE, $batch_size);
        $hard_limit = min(self::ALL_HARD_LIMIT, $query['limit'] ?? self::ALL_HARD_LIMIT);
        $offset = 0;
        $combined = [];
        while($hard_limit > 0) {
            $limit = min($hard_limit, $batch_size);

            $query['limit'] = $limit;
            $query['offset'] = $offset;
            $offset += $batch_size;
            $hard_limit -= $batch_size;

            $a_response = $this->request($uri, $query, $extra, $method);
            if(!$combined) {
                $combined = $a_response;
            } else {
                $combined['items'] = array_merge($combined['items'], $a_response['items']);
            }

            if(empty($a_response['next'])) {
                break;
            }
        }

        return $combined;
    }


    private function request($uri, $query=[], $extra=[], $method='GET') {

        try {
            $extra['query'] = $query;
            $extra['headers'] = ["Authorization"=>"Bearer " . $this->getToken()];
            $response = $this->client->request($method, $uri, $extra);
        } catch (GuzzleHttp\Exception\ClientException $gece) {
            // retry auth, maybe token timeout (1h)
            $this->authorize();
            $extra['headers'] = ["Authorization"=>"Bearer " . $this->getToken()];
            $response = $this->client->request($method, $uri, $extra);
        } catch (\Throwable $th) {
            throw new $th;
        }

        $body = $response->getBody()->getContents();
        $data = @json_decode($body, true);
        if (!$data) {
            $data = ['raw response'=>$body];
        }

        return $data;
    }

    private function getToken() {

        if(!$this->access_token) {
            if(file_exists($this->fs_token_file)) {
                $this->access_token = file_get_contents($this->fs_token_file);
            }else{
                $this->authorize();
            }
        }
        return $this->access_token;
    }

    private function authorize() {

        $b64 = base64_encode($this->clientID . ":" . $this->clientSecret);

        $response = $this->client->post('https://accounts.spotify.com/api/token', [
            'headers' => ["Authorization"=>"Basic $b64"],
            'form_params'=> ["grant_type"=>"client_credentials"]
        ]);

        if($response->getStatusCode() != 200) {
            throw new Exception('Auth error', $response->getStatusCode());
        }

        $body = @json_decode($response->getBody()->getContents());

        $this->access_token = $body->access_token;
        file_put_contents($this->fs_token_file, $this->access_token);
    }
}
