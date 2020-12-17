<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

require __DIR__ . '/../vendor/autoload.php';


class Spotify {
    private $client = null;
    private $clientID = null;
    private $clientSecret = null;
    private $access_token = null;
    private $fs_token_file = null;


    public function __construct($clientID, $clientSecret) {

        $this->clientID = $clientID;
        $this->clientSecret = $clientSecret;
        $this->fs_token_file = sys_get_temp_dir() . '/spotify.token';
        $this->client = new GuzzleHttp\Client();
    }


    public function search($keywords, $limit=10, $type='artist', $market='AR') {

        $uri = 'https://api.spotify.com/v1/search';

        $response = $this->request($uri, [
            'query' => [
                'q' => $keywords,
                'type' => $type,
                'market' => $market,
                'limit' => $limit,
            ],
        ]);

        $result = $response;

        $result = [];
        foreach ($response as $atype => $typeResults) {
            if(isset($typeResults['items'])){
                $result[$atype] = [];
                foreach ($typeResults['items'] as $key => $value) {
                    $result[$atype][$value['id']] = $value['name'];
                }
            }
        }

        return $result;
    }


    // https://open.spotify.com/artist/2ye2Wgw4gimLv2eAKyk1NB?si=uk379ferRDiqgoYBNb52Og
    // https://open.spotify.com/track/2MuWTIM3b0YEAskbeeFE1i
    public function getAlbums($artist_id = 'uk379ferRDiqgoYBNb52Og') {
        $uri = 'https://api.spotify.com/v1/artists/$artist_id/albums?market=ES&limit=50';


        $response = $this->client->get($uri, ['headers' => ["Authorization"=>"Bearer {$this->access_token}"]]);

        // if($response->getStatusCode() != 200) {
        //     throw new Exception('status code: ' . $response->getStatusCode());
        // }
        $body = $response->getBody()->getContents();
        $data = @json_decode($body);
        if (!$data) {
            $data = ['raw response'=>$body];
        }
        $data['status-code'] = $response->getStatusCode();
        return $data;
    }


    private function getArtistData($artist){

    }

    private function request($uri, $extra = [], $method = 'GET'){
        echo("->request()\n");

        try {
            $extra['headers'] = ["Authorization"=>"Bearer " . $this->getToken()];
            $response = $this->client->request($method, $uri, $extra);
        } catch (GuzzleHttp\Exception\ClientException $gece) {
            echo(' retry auth, maybe token timeout\n');
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
        echo("->getToken()\n");
        if(!$this->access_token) {
            if(file_exists($this->fs_token_file)) {
                echo(" found token fs cache\n");
                $this->access_token = file_get_contents($this->fs_token_file);
            }else{
                $this->authorize();
            }
        } else {
            echo(" found token in instance\n");
        }
        return $this->access_token;
    }

    private function authorize() {
        echo("->authorize()\n");

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
