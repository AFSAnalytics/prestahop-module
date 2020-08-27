<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/api/api.php';

include_once 'result.php';

class AFSAApiRequest
{
    private $api;
    private $logged;
    private $requested_actions;

    public function __construct()
    {
        if (Tools::getValue('account_id') === AFSAConfig::DEMO_ACCOUNT_ID) {
            AFSAConfig::setDemoMode();
        }

        $this->requested_actions = Tools::getValue('actions');
        $this->context = Tools::getValue('context');
    }

    public function run()
    {
        return $this->sendBatch();
    }

    private function validate()
    {
        return !empty($this->requested_actions);
    }

    private function logIn()
    {
        $this->api = new AFSAApi();

        return $this->logged = $this->api->isLogged();
    }

    public function logOut()
    {
        $this->api->logout();
        $this->logged = false;
    }

    public function sendBatch()
    {
        AFSATools::log(__METHOD__);
        AFSATools::log('[PS REQUEST BATCH] actions: ' . json_encode($this->requested_actions, JSON_PRETTY_PRINT));

        $ret = null;
        if ($this->validate()) {
            if (!$this->logIn()) {
                return array('error' => 401);
            }

            $ret = $this->api->post('/stats/batch', ['actions' => $this->requested_actions]);
        }

        $result = new AFSAApiRequestResult($this, $ret);

        return $result->render();
    }
}
