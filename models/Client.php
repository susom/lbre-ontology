<?php
namespace Stanford\LBRE;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

class Client extends \GuzzleHttp\Client
{
    private $enc_credentials;

    private $em;

    public function __construct($em, array $config = ['Content-Type' => 'application/json'])
    {
        parent::__construct($config);

        $this->setEm($em);

    }

    public function generateBearerToken($url){
        $options = [
            'headers' => ['Authorization' => 'Basic ' . $this->getEncCredentials()]
        ];
        $response = $this->createRequest('get',$url, $options);

        $this->getEm()->emLog('Bearer response:');
        $this->getEm()->emLog($response);

        return $response;

    }

    /**
     * Sends a guzzle request
     * @param $method
     * @param $uri
     * @param array $options
     * @return mixed|string|void
     */
    public function createRequest($method, $uri = '', array $options = []){
        try {
            $response = parent::request($method, $uri, $options);
            $code = $response->getStatusCode();
            $this->getEm()->emLog($response);
            if ($code == 200 || $code == 201 || $code == 202) {
                $content = $response->getBody()->getContents();
                if (is_array(json_decode($content, true))) {
                    return json_decode($content, true);
                }

                return $content;
            } else {
                throw new \Exception("Request has failed: $response");
            }

        } catch (\Exception $e) {
            \REDCap::logEvent("Error: $e");
            $this->getEm()->emError($e);
        } catch (\GuzzleException $e) {
            \REDCap::logEvent("Error: $e");
            $this->getEm()->emError($e);
        }
    }


    /**
     * @param $login
     * @param $password
     * @return void
     * Sets the basic auth credentials needed for requests
     */
    public function setEncCredentials($login, $password){
        if($login && $password)
            $this->enc_credentials = base64_encode("$login:$password");
    }

    public function getEncCredentials(){
        return $this->enc_credentials;
    }

    /**
     * @return mixed
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * @param mixed $em
     */
    public function setEm($em): void
    {
        $this->em = $em;
    }

}
