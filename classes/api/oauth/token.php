<?php

class AFSAOAuthToken
{
    private $key;
    private $account_id;
    private $tokens = [];

    const KEY = 'AFS_ANALYTICS_OAUTH_TOKEN';

    public function __construct($account_id)
    {
        $this->account_id = $account_id;
        $this->loadAll();
    }

    public function load()
    {
        return $this->getAccessToken() != null;
    }

    public function loadAll()
    {
        $json = Configuration::get(static::KEY);
        $this->tokens = $json ? json_decode($json, 1) : null;
    }

    public function save()
    {
        Configuration::updateValue(static::KEY, json_encode($this->tokens));
    }

    public function clear()
    {
        Configuration::deleteByName(static::KEY);
    }

    public function set($data, $save = true)
    {
        AFSATools::log('TOKEN', __METHOD__, $data);

        if (empty($data) || empty($data['access_token'])) {
            return false;
        }

        if (empty($data['expires_at'])) {
            $data['expires_at'] = time() + $data['expires_in'];
        }

        $p = parse_url(AFSAConfig::getAFSAHome());
        $data['host'] = $p['host'];

        $this->tokens[$this->account_id] = $data;

        if ($save) {
            $this->save();
        }

        return true;
    }

    public function getData()
    {
        return empty($this->tokens[$this->account_id]) ?
                null :
                $this->tokens[$this->account_id]
        ;
    }

    private function getValue($field)
    {
        $data = $this->getData();
        if (empty($data)) {
            return null;
        }

        return empty($data) || empty($data[$field]) ?
                null : $data[$field]
        ;
    }

    public function getAccessToken()
    {
        return $this->getValue('access_token');
    }

    public function getRefreshToken()
    {
        return $this->getValue('refresh_token');
    }

    public function isExpired($min_seconds = 60)
    {
        return $this->secondsBeforeExpiration() < $min_seconds;
    }

    public function secondsBeforeExpiration()
    {
        AFSATools::log('E:' . $this->getValue('expires_at') . ' N:' . time());

        return $this->getValue('expires_at') - time();
    }

    public function daysBeforeExpiration()
    {
        return floor($this->secondsBeforeExpiration() / (3600 * 24));
    }

    public function hoursBeforeExpiration()
    {
        return floor($this->secondsBeforeExpiration() / 3600);
    }

    /*
      public function dump()
      {
      AFSATools::log(
      __METHOD__, array_merge(
      $this->getData(), [
      'sec' => $this->secondsBeforeExpiration(),
      'hours' => $this->hoursBeforeExpiration(),
      'days' => $this->daysBeforeExpiration(),
      'isExpired' => $this->isExpired(),
      ]));
      } */
}
