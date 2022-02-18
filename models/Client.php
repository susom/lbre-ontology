<?php
namespace Stanford\LBRE;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class Client extends \GuzzleHttp\Client
{
    private $enc_credentials;

    public function __construct(array $config = ['Content-Type' => 'application/json'])
    {
        parent::__construct($config);

    }

    public function generateBearerToken(){
        $options = [
            'headers' => ['Authorization' => 'Basic ' . $this->getEncCredentials()]
        ];

        return $this->createRequest('get','https://aswsdev.stanford.edu/api/oauth/jwttoken', $options);

    }

    public function createRequest($method, $uri = '', array $options = []){
        try {
            $response = parent::request($method, $uri, $options);
            $code = $response->getStatusCode();

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
            $this->emError($e->getMessage());
        } catch (GuzzleException $e) {
            $this->emError($e->getMessage());
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

}
