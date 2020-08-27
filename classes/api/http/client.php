<?php

use GuzzleHttp\Exception\ClientException;

class AFSAHTTPclient
{
    private $client_;
    private $response_;

    public function __construct()
    {
        $this->client_ = new \GuzzleHttp\Client([]);

        $this->user_agent_ = 'AFSA prestahop plugin v' . AFSA_MODULE_VERSION;
    }

    public function getVersion()
    {
        return \GuzzleHttp\Client::VERSION;
    }

    // REQUEST

    public function post($url, $arr)
    {
        $headers = ['User-Agent' => $this->user_agent_];
        try {
            $this->response_ = method_exists($this->client_, 'request') ?
                    $this->client_->request('POST', $url, ['form_params' => $arr, 'headers' => $headers]) :
                    $this->client_->post($url, ['body' => $arr, 'headers' => $headers]);
        } catch (ClientException $e) {
            AFSATools::log($e->getMessage());
            $this->response_ = $e->getResponse();
        }

        return $this->response_;
    }

    public function get($url, $arr)
    {
        try {
            $this->response_ = method_exists($this->client_, 'request') ?
                    $this->client_->request('GET', $url, ['query' => $arr]) :
                    $this->client_->get($url, ['query' => $arr]);
        } catch (ClientException $e) {
            AFSATools::log($e->getMessage());
            $this->response_ = $e->getResponse();
        }

        return $this->response_;
    }

    public function getJSON()
    {
        return empty($this->response_) ? null : json_decode($this->response_->getBody(), true);
    }

    public function getHeaders()
    {
    }

    public function getBody()
    {
    }
}
