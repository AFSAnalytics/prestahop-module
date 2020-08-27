<?php

include_once 'token.php';
include_once 'demo.token.php';

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/api/http/client.php';

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/config/main.php';
include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/tools.php';

use AFSAOAuthToken as token;
use AFSAOAuthDemoToken as demoToken;

class AFSAOAuthClient
{
    private $oauth_server_url = null;
    private $token = null;
    private $client_id;
    private $client_secret = '';
    private $url;
    private $account_id;

    public function __construct($account_id, $callback_url)
    {
        $this->oauth_server_url = AFSAConfig::getOauthServerURL();

        $this->url = array(
            'authorize' => $this->oauth_server_url . 'auth/' . $account_id . '/',
            'token' => $this->oauth_server_url . 'token',
            'resource' => $this->oauth_server_url . '',
            'callback' => $callback_url,
        );

        AFSATools::log(__METHOD__, $callback_url);

        $this->client_id = AFSAConfig::getOauthClientID();

        $this->account_id = $account_id;
        $this->token = AFSAConfig::isDemo() ?
                new demoToken($account_id) :
                new token($account_id);
    }

    public function getResourceURL()
    {
        return $this->url['resource'];
    }

    public function isLogged()
    {
        if ($this->token->load()) {
            return !$this->token->isExpired();
        }

        return false;
    }

    /**
     * only login, do not request auth code if no token
     */
    public function simpleLogin()
    {
        return $this->login(true);
    }

    public function login($no_authorization_request = false)
    {
        AFSATools::log(__METHOD__);

        if ($this->token->load()) {
            AFSATools::log(__METHOD__, $this->token->getData());

            AFSATools::log('expires in', $this->token->daysBeforeExpiration(), $this->token->isExpired());

            if (!$this->token->isExpired()) {
                return true;
            }

            // On Expired Token

            AFSATools::log('Refreshing token');

            // getting a new access token from stored refresh token
            if ($this->refreshToken()) {
                return true;
            }

            // could not obtain a new access token
            // => clear current token and restart auth process
            $this->token->clear();
            $this->token = new token($this->account_id);

            if ($no_authorization_request) {
                return false;
            } else {
                $this->requestAuthorizationCode();
            }

            return true;
        }

        AFSATools::log('cant load token');

        if (Tools::getValue('noredir')) {
            return false;
        }

        if (Tools::getValue('code')) {
            $ret = $this->onAuthorizationCodeReceived();

            return empty($ret['error']);
        }

        if ($no_authorization_request) {
            return false;
        } else {
            $this->requestAuthorizationCode();
        }

        return false;
    }

    public function logout()
    {
        $this->token->clear();
    }

    /**
     * Redirect to Server Authorization page to request an authorization code.
     */
    private function requestAuthorizationCode()
    {
        $state = md5(random_bytes(32));
        Configuration::updateValue('AFS_ANALYTICS_OAUTH_STATE', $state);

        $params = array(
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'client_api' => AFSA_MODULE_VERSION,
            //'scope' => '',
            'redirect_uri' => $this->url['callback'],
            'state' => $state,
            'approval_prompt' => 'auto',
            'access_type' => 'offline',
            'interact' => 1,
            'resolve_account' => AFSAConfig::getConfigControllerURL(),
        );

        AFSAConfig::saveOauthCallbackURL($this->url['callback']);

        AFSATools::log(__METHOD__, $params);

        Tools::redirectLink($this->url['authorize'] . '?' . http_build_query($params));
        exit;
    }

    public function onAuthorizationCodeReceived()
    {
        $ret = array(
            'error' => true,
            'msg' => array(),
        );

        /*
          AFSATools::log(
          __METHOD__,
          array($_REQUEST,
          Tools::getValue('state'),
          Configuration::get('AFS_ANALYTICS_OAUTH_STATE')
          ));
         */

        if (Tools::getValue('state') !== Configuration::get('AFS_ANALYTICS_OAUTH_STATE')) {
            $ret['msg'][] = AFSAConfig::TR('oauth_invalid_state');
        }

        $code = filter_var(Tools::getValue('code'), FILTER_SANITIZE_STRING);

        // RETRIEVE TOKEN FROM CODE

        $client = new AFSAHTTPclient();

        $r = $client->post($this->url['token'],
                array(
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $this->client_id,
                    'client_api' => AFSA_MODULE_VERSION,
                    // retrieving url saved by  requestAuthorizationCode()
                    'redirect_uri' => AFSAConfig::getOauthCallbackURL(),
                )
        );

        $data = $client->getJSON();

        /*
          AFSATools::log(__METHOD__,
          array(
          'url' => $this->url['token'],
          'grant_type' => 'authorization_code',
          'code' => $code,
          'client_id' => $this->client_id,
          'client_api' => AFSA_MODULE_VERSION,
          'redirect_uri' => $this->url['callback'],
          )
          );
         */

        if (empty($data)) {
            $ret['msg'][] = 'Empty data';
        } elseif (!empty($data['error'])) {
            $ret['msg'][] = $data['error_description'];

            switch ($r->getStatusCode()) {
                //Authorization code is invalide
                //need to restart Auth process
                case 400:
                    // TODO
                    //$this->requestAuthorizationCode();
                    break;
            }
        } else {
            $this->token->set($data, true);
            $ret['error'] = false;
        }

        return $ret;
    }

    // TOKEN RELATED FUNCTIONS

    public function getToken()
    {
        return $this->token;
    }

    public function getAccessToken()
    {
        return $this->token->getAccessToken();
    }

    public function deleteToken()
    {
        $this->token->clear();
    }

    public function getTokenData()
    {
        return $this->token->getData();
    }

    private function refreshToken()
    {
        $client = new AFSAHTTPclient(['auth' => [$this->client_id, $this->client_secret]]);

        // If we have a refresh token try to get new token
        if (!empty($this->token->getRefreshToken())) {
            $client->post($this->url['token'], array(
                'grant_type' => 'refresh_token',
                'client_id' => $this->client_id,
                'client_api' => AFSA_MODULE_VERSION,
                'refresh_token' => $this->token->getRefreshToken(),
            ));

            $data = $client->getJSON();

            $data['method'] = __METHOD__;

            //AFSATools::log(json_encode($data, JSON_PRETTY_PRINT));

            if (empty($data['error'])) {
                $this->token->set($data, true);

                return true;
            }
        }

        return false;
    }
}
