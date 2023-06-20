<?php namespace App\Repos;

use Curl\Curl;

class DotaRepo {

    private $steamID;
    // private $curl;
    private $api_key_open_dota = "84b24242-4fe0-46ac-87a7-efca3ff28117";

    private $api_key = "1EDC0D204A7716E809F0B2DABE207BE7";

    public function __construct($steamID = null)
    {
        if($steamID !== null)
            $this->steamID = $steamID;

        $this->curl = new Curl("https://api.opendota.com/api/");
    }

    public function findMatch($matchID){
        $response = file_get_contents("https://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/v1/?key=".$this->api_key."&match_id=".$matchID);
        $response = json_decode($response,true);
        return $response['result'];
    }

    public function setSteamID($steamID){
        $this->steamID = $steamID;
    }

    public function getRecentMatches(){

        $response = file_get_contents('https://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/v1/?key='.$this->api_key.'&account_id='.$this->steamID);
        $response = json_decode($response,true);
        return $response['result']['matches'];
    }


    public function getMatchesFromOpenDota(){
        $this->curl->get("players/".$this->steamID."/recentMatches?api_key=".$this->api_key_open_dota);
        return $this->curl->response;
    }
}
