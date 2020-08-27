<?php

include_once 'oauth/client.php';

class AFSAApi
{
    private $access_token;
    private $http_client;
    private $oauth_client;
    private $client_id;
    private $server_url;
    private $account_id;
    private $callback_url;

    public function __construct($cfg = array())
    {
        if (!empty($cfg['callback_url'])) {
            $this->callback_url = $cfg['callback_url'];
        }

        $this->client_id = AFSAConfig::getOauthClientID();

        $this->account_id = empty($cfg['account_id']) ?
                AFSAConfig::getAccountID() :
                $cfg['account_id'];
    }

    public function simpleLogin()
    {
        return $this->login(['simple_login' => true]);
    }

    public function login($params = array())
    {
        if (empty($this->account_id)) {
            return false;
        }

        $c = $this->getOAuthClient();

        $this->server_url = $c->getResourceURL();

        $ret = empty($params['simple_login']) ?
                $c->login() :
                $c->simpleLogin()
        ;

        if ($ret) {
            $this->access_token = $c->getAccessToken();
        }

        return $ret;
    }

    public function isLogged()
    {
        $c = $this->getOAuthClient();
        if ($c->isLogged()) {
            $this->server_url = $c->getResourceURL();
            $this->access_token = $c->getAccessToken();

            return true;
        }

        return false;
    }

    public function getAccessToken()
    {
        return $this->access_token;
    }

    public function logout()
    {
        $this->getOAuthClient()->logout();
    }

    private function getOAuthClient()
    {
        return empty($this->oauth_client) ?
                $this->oauth_client = new AFSAOAuthClient($this->account_id,
                $this->callback_url ?
                $this->callback_url :
                AFSAConfig::getCurrentURL()
                ) :
                $this->oauth_client;
    }

    private function getHTTPClient()
    {
        return empty($this->http_client) ?
                $this->http_client = new AFSAHTTPclient() :
                $this->http_client
        ;
    }

    private function prepareParams(&$p)
    {
        foreach (array('client_id' => $this->client_id, 'access_token' => $this->access_token) as $k => $v) {
            $p[$k] = $v;
        }

        return $p;
    }

    /**
     * SEND request.
     *
     * @param string $endpoint
     * @param array $param
     *
     * @return array
     */
    public function sendRequest($endpoint, $params = [], $method = 'get')
    {
        if (empty($this->access_token)) {
            AFSATools::log(__METHOD__ . ' empty token');

            return null;
        }

        $this->prepareParams($params);
        $url = chop($this->server_url, '/') . $endpoint;

        AFSATools::log('[PS]API ' . $method . ': ' . $url . PHP_EOL . json_encode($params, JSON_PRETTY_PRINT));

        $method == 'post' ?
                        $this->getHTTPClient()->post($url, $params) :
                        $this->getHTTPClient()->get($url, $params)
        ;

        return $this->getHTTPClient()->getJSON();
    }

    public function get($endpoint, $params = [])
    {
        return $this->sendRequest($endpoint, $params, 'get');
    }

    public function post($endpoint, $params = [])
    {
        return $this->sendRequest($endpoint, $params, 'post');
    }
}
