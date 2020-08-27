<?php

include_once _PS_MODULE_DIR_ . '/afsanalytics/classes/account/account.php';

class AFSAAccountManager
{
    private static $instance;
    private $accounts = array();

    public static function get()
    {
        return static::$instance ?
                static::$instance :
                static::$instance = new AFSAAccountManager();
    }

    public static function validateID($id)
    {
        return !empty($id) && ctype_digit($id) && Tools::strlen($id) == 8 && (int) $id > 0;
    }

    public static function onAjaxInfosReceived($data)
    {
        error_log('[account] AMGR ' . json_encode($data));
        if (empty($data['plan']) || empty($data['id'])) {
            return;
        }

        $account_id = $data['id'];
        $account = new AFSAAccount($account_id);
        $account->updatePlanFromData($data['plan']);
    }

    public function getCurrent($forced_id = null)
    {
        $id = $forced_id ? $forced_id : AFSAConfig::getAccountID();
        if (!$id) {
            return null;
        }

        if (empty($this->accounts[$id])) {
            $this->accounts[$id] = new AFSAAccount($id);
        }

        return $this->accounts[$id];
    }

    public function setCurrentID($id)
    {
        if (static::validateID($id)) {
            Configuration::updateValue('AFS_ANALYTICS_ACCOUNT', $id);
        }
    }

    public function setCurrent($id)
    {
        if (!static::validateID($id)) {
            return null;
        }
        $this->setCurrentID($id);

        return $this->getCurrent();
    }

    // ACCOUNT CREATION

    public function getAccountCreationParams($return_url = null)
    {
        $ret = AFSAConfig::getInfosManager()->site()->get();

        if (AFSAConfig::getPAARC()) {
            $ret['paa_rc'] = AFSAConfig::getPAARC();
        }

        $ret['return_url'] = $return_url ?
                $return_url :
                AFSAConfig::getAccountManagerURL();

        $state = md5(random_bytes(32));
        AFSAConfig::saveRequestState($state);
        $ret['state'] = $state;

        return $ret;
    }
}
