<?php

define('AFSA_PRESTASHOP_DEMO_TOKEN', '6ae89a6bd587bg85a1898feb7a6bd5d1');

class AFSAOAuthDemoToken
{
    private $data;

    public function save()
    {
    }

    public function load()
    {
        $this->data = [
            'access_token' => AFSA_PRESTASHOP_DEMO_TOKEN,
        ];

        return $this->getAccessToken() != null;
    }

    public static function clear()
    {
    }

    public function set()
    {
        return true;
    }

    public function getData()
    {
        return $this->data;
    }

    private function getValue($field)
    {
        return empty($this->data[$field]) ? null : $this->data[$field];
    }

    public function getAccessToken()
    {
        return $this->getValue('access_token');
    }

    public function getRefreshToken()
    {
        return $this->getValue('refresh_token');
    }

    public function isExpired()
    {
        return false;
    }

    public function secondsBeforeExpiration()
    {
        return 9999;
    }

    public function daysBeforeExpiration()
    {
        return 9999;
    }

    public function hoursBeforeExpiration()
    {
        return 9999;
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
        }
        */
}
