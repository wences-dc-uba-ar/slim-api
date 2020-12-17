<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;


require __DIR__ . '/../vendor/autoload.php';


class Spotify {

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


    public static function search($keywords, $limit=10, $offset=0, $type='artist', $market='AR') {

        // https://developer.spotify.com/documentation/web-api/reference-beta/#category-search
        $uri = "https://api.spotify.com/v1/search";

        $response = self::getInstance()->request($uri, [
            'query' => [
                'q' => $keywords,
                'type' => $type,
                'market' => $market,
                'limit' => $limit,
                'offset' => $offset,
            ],
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


    public static function getAlbums($artist_id, $limit=10, $offset=0, $market='AR', $include_groups='album,single') {

        // https://developer.spotify.com/documentation/web-api/reference-beta/#endpoint-get-an-artists-albums
        $uri = "https://api.spotify.com/v1/artists/$artist_id/albums";

        $response = self::getInstance()->request($uri, [
            'query' => [
                'market' => $market,
                'limit' => $limit,
                'offset' => $offset,
                'include_groups' => $include_groups,
            ]
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


    private function requestAll($uri, $extra = [], $method = 'GET') {

        // TODO: make the request for all pages
        // "limit": 10,
        // "next": "https://api.spotify.com/v1/artists/1DFr97A9HnbV3SKTJFu62M/albums?offset=10&limit=10&include_groups=album,single&market=AR",
        // "offset": 0,
        // "previous": null,
        // "total": 64
    }


    private function request($uri, $extra = [], $method = 'GET') {

        try {
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

        // if($response->getStatusCode() != 200) {
        //     throw new Exception('status code: ' . $response->getStatusCode());
        // }
        $body = $response->getBody()->getContents();
        $data = @json_decode($body, true);
        if (!$data) {
            $data = ['raw response'=>$body];
        }
        // $data['status-code'] = $response->getStatusCode();
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
            throw new Exception('Auth status code: ' . $response->getStatusCode());
        }

        $body = @json_decode($response->getBody()->getContents());

        $this->access_token = $body->access_token;
        file_put_contents($this->fs_token_file, $this->access_token);
    }
}
